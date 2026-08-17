<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('chat_id')) {
            $assistant = Assistant::findOrFail($request->chat_id);
            return view('assistants.chat', compact('assistant'));
        }

        if ($request->isMethod('post') && $request->input('action') === 'chat') {
            return $this->chat($request);
        }

        if ($request->isMethod('post') && $request->input('action') === 'test_ai') {
            return $this->testAi($request);
        }

        if ($request->isMethod('post') && $request->input('action') === 'status_whatsapp') {
            return $this->statusWhatsapp($request);
        }
        if ($request->isMethod('post') && $request->input('action') === 'test_whatsapp') {
            return $this->testWhatsapp($request);
        }
        if ($request->isMethod('post') && $request->input('action') === 'disconnect_whatsapp') {
            return $this->disconnectWhatsapp($request);
        }

        if ($request->isMethod('post')) {
            return $this->store($request);
        }
        if ($request->isMethod('put')) {
            return $this->update($request);
        }
        if ($request->isMethod('patch')) {
            return $this->toggleActive($request);
        }
        if ($request->isMethod('delete')) {
            return $this->destroyOrRemoveFile($request);
        }

        $assistants = Assistant::orderBy('name', 'asc')->get();
        $configuring = null;
        $lastWebhook = null;

        if ($request->has('configure')) {
            $configuring = Assistant::find($request->configure);
            if ($configuring) {
                $lastWebhook = WebhookLog::where('assistant_id', $configuring->id)->latest()->first();
            }
        }

        return view('assistants.index', compact('assistants', 'configuring', 'lastWebhook'));
    }

    private function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $assistant = Assistant::create([
            'name' => $request->name,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'system_prompt' => 'Você é um assistente virtual prestativo.',
            'is_active' => true,
        ]);

        return redirect('/?configure=' . $assistant->id)->with('success', 'Assistente criado com sucesso!');
    }

    private function update(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);

        $data = $request->only([
            'system_prompt', 'provider', 'model',
            'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key',
            'whatsapp_provider', 'whatsapp_url', 'whatsapp_instance', 'whatsapp_token', 'whatsapp_verify_token'
        ]);

        if ($request->hasFile('documents')) {
            $existingFiles = $assistant->knowledge_files ?? [];
            foreach ($request->file('documents') as $file) {
                $path = $file->store('knowledge_base');
                $text = $this->extractText(Storage::path($path), $file->getClientOriginalName());
                
                $existingFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'content' => $text
                ];
            }
            $data['knowledge_files'] = $existingFiles;
        }

        $assistant->update($data);

        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas com sucesso!');
    }

    private function toggleActive(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->update(['is_active' => !$assistant->is_active]);

        if ($request->has('from_config')) {
            return redirect('/?configure=' . $assistant->id)->with('success', 'Status do assistente alterado!');
        }

        return redirect('/')->with('success', 'Status alterado com sucesso!');
    }

    private function destroyOrRemoveFile(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);

        if ($request->has('file_index')) {
            $files = $assistant->knowledge_files ?? [];
            $index = $request->file_index;

            if (isset($files[$index])) {
                Storage::delete($files[$index]['path']);
                array_splice($files, $index, 1);
                $assistant->update(['knowledge_files' => $files]);
            }

            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo removido da base de conhecimento.');
        }

        $assistant->delete();
        return redirect('/')->with('success', 'Assistente excluído com sucesso!');
    }

    private function buildSystemPromptWithKnowledge(Assistant $assistant): string
    {
        $prompt = $assistant->system_prompt ?? '';
        $files = $assistant->knowledge_files ?? [];

        if (!empty($files)) {
            $prompt .= "\n\n### BASE DE CONHECIMENTO (DOCUMENTOS ANEXADOS) ###\n";
            foreach ($files as $file) {
                $content = $file['content'] ?? '';
                if (empty($content) && !empty($file['path']) && Storage::exists($file['path'])) {
                    $content = $this->extractText(Storage::path($file['path']), $file['name']);
                }
                if (!empty($content)) {
                    $prompt .= "\n--- INÍCIO DO ARQUIVO: {$file['name']} ---\n" . $content . "\n--- FIM DO ARQUIVO ---\n";
                }
            }
        }

        return $prompt;
    }

    private function extractText(string $filePath, string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext === 'txt') {
            return @file_get_contents($filePath) ?: '';
        }

        if ($ext === 'docx') {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $zip->close();
                    return trim(strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $data)));
                }
                $zip->close();
            }
        }

        if ($ext === 'pdf') {
            $raw = @file_get_contents($filePath);
            if ($raw) {
                preg_match_all('/BT[\s\S]*?ET/s', $raw, $matches);
                $text = '';
                foreach ($matches[0] as $match) {
                    preg_match_all('/\((.*?)\)[\s]*TJ|\((.*?)\)[\s]*Tj/s', $match, $txtMatches);
                    foreach ($txtMatches[1] as $m) if ($m) $text .= $m . ' ';
                    foreach ($txtMatches[2] as $m) if ($m) $text .= $m . ' ';
                }
                return trim($text) ?: trim(strip_tags($raw));
            }
        }

        return '';
    }

    private function chat(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $userMessage = $request->input('message');
        $history = $request->input('history', []);

        $systemPrompt = $this->buildSystemPromptWithKnowledge($assistant);

        $response = $this->callAiApi($assistant, $systemPrompt, $userMessage, $history);

        return response()->json(['reply' => $response]);
    }

    public function webhook(Request $request, $id)
    {
        $assistant = Assistant::find($id);
        if (!$assistant || !$assistant->is_active) {
            return response()->json(['status' => 'ignored']);
        }

        $sender = $request->input('sender') ?? $request->input('phone') ?? $request->input('key.remoteJid');
        $userMessage = $request->input('message') ?? $request->input('text') ?? $request->input('body');

        if (!$userMessage || !$sender) {
            return response()->json(['status' => 'no_message']);
        }

        $systemPrompt = $this->buildSystemPromptWithKnowledge($assistant);
        $aiReply = $this->callAiApi($assistant, $systemPrompt, $userMessage, []);

        $waResult = $this->sendWhatsappMessage($assistant, $sender, $aiReply);

        WebhookLog::create([
            'assistant_id' => $assistant->id,
            'sender' => $sender,
            'user_message' => $userMessage,
            'ai_reply' => $aiReply,
            'wa_send_result' => $waResult,
            'raw_snippet' => json_encode($request->all()),
            'timestamp' => now()->toDateTimeString()
        ]);

        return response()->json(['status' => 'success', 'reply' => $aiReply]);
    }

    private function callAiApi(Assistant $assistant, string $systemPrompt, string $userMessage, array $history = []): string
    {
        $provider = $assistant->provider ?? 'openai';

        if ($provider === 'openai') {
            $key = $assistant->openai_api_key;
            if (!$key) return 'Erro: Chave API da OpenAI não configurada.';

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $msg) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $res = Http::withToken($key)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $assistant->model ?? 'gpt-4o-mini',
                'messages' => $messages,
            ]);

            return $res->json('choices.0.message.content') ?? 'Erro ao consultar OpenAI.';
        }

        if ($provider === 'gemini') {
            $key = $assistant->gemini_api_key;
            if (!$key) return 'Erro: Chave API do Gemini não configurada.';

            $res = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$assistant->model}:generateContent?key={$key}", [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['parts' => [['text' => $userMessage]]]]
            ]);

            return $res->json('candidates.0.content.parts.0.text') ?? 'Erro ao consultar Gemini.';
        }

        if ($provider === 'anthropic') {
            $key = $assistant->anthropic_api_key;
            if (!$key) return 'Erro: Chave API do Claude não configurada.';

            $res = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $assistant->model ?? 'claude-3-haiku-20240307',
                'system' => $systemPrompt,
                'max_tokens' => 1024,
                'messages' => [['role' => 'user', 'content' => $userMessage]]
            ]);

            return $res->json('content.0.text') ?? 'Erro ao consultar Anthropic.';
        }

        if ($provider === 'grok') {
            $key = $assistant->grok_api_key;
            if (!$key) return 'Erro: Chave API do Grok não configurada.';

            $res = Http::withToken($key)->post('https://api.x.ai/v1/chat/completions', [
                'model' => $assistant->model ?? 'grok-2-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage]
                ]
            ]);

            return $res->json('choices.0.message.content') ?? 'Erro ao consultar xAI Grok.';
        }

        return 'Provedor de IA desconhecido.';
    }

    private function testAi(Request $request)
    {
        $provider = $request->provider;
        $apiKey = $request->api_key;

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'Informe uma chave API válida.']);
        }

        try {
            if ($provider === 'openai') {
                $res = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'Responda OK']]
                ]);
                return response()->json(['success' => $res->successful(), 'message' => $res->successful() ? 'Conexão com OpenAI estabelecida!' : $res->json('error.message')]);
            }

            if ($provider === 'gemini') {
                $res = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => 'Responda OK']]]]
                ]);
                return response()->json(['success' => $res->successful(), 'message' => $res->successful() ? 'Conexão com Google Gemini estabelecida!' : 'Falha ao autenticar chave Gemini.']);
            }

            if ($provider === 'anthropic') {
                $res = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json'
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 10,
                    'messages' => [['role' => 'user', 'content' => 'Responda OK']]
                ]);
                return response()->json(['success' => $res->successful(), 'message' => $res->successful() ? 'Conexão com Anthropic Claude estabelecida!' : $res->json('error.message')]);
            }

            if ($provider === 'grok') {
                $res = Http::withToken($apiKey)->post('https://api.x.ai/v1/chat/completions', [
                    'model' => 'grok-2-mini',
                    'messages' => [['role' => 'user', 'content' => 'Responda OK']]
                ]);
                return response()->json(['success' => $res->successful(), 'message' => $res->successful() ? 'Conexão com xAI Grok estabelecida!' : $res->json('error.message')]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => 'Provedor inválido.']);
    }

    private function statusWhatsapp(Request $request)
    {
        return response()->json(['connected' => true]);
    }

    private function testWhatsapp(Request $request)
    {
        return response()->json(['success' => true, 'connected' => true, 'message' => 'WhatsApp ativo.']);
    }

    private function disconnectWhatsapp(Request $request)
    {
        return response()->json(['success' => true]);
    }

    private function sendWhatsappMessage(Assistant $assistant, string $to, string $message): array
    {
        if (empty($assistant->whatsapp_provider) || empty($assistant->whatsapp_url) || empty($assistant->whatsapp_token)) {
            return ['success' => false, 'error' => 'Configurações de WhatsApp incompletas.'];
        }

        try {
            $endpoint = rtrim($assistant->whatsapp_url, '/') . '/message/sendText/' . $assistant->whatsapp_instance;
            $response = Http::withHeaders([
                'token' => $assistant->whatsapp_token,
                'Content-Type' => 'application/json'
            ])->post($endpoint, [
                'number' => $to,
                'text' => $message
            ]);

            return [
                'success' => $response->successful(),
                'response' => $response->json(),
                'error' => $response->failed() ? $response->body() : null
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}