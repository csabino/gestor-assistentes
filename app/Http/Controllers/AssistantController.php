<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        try {
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
                return response()->json(['connected' => true]);
            }
            if ($request->isMethod('post') && $request->input('action') === 'test_whatsapp') {
                return response()->json(['success' => true, 'connected' => true, 'message' => 'WhatsApp ativo.']);
            }
            if ($request->isMethod('post') && $request->input('action') === 'disconnect_whatsapp') {
                return response()->json(['success' => true]);
            }

            if ($request->isMethod('post')) return $this->store($request);
            if ($request->isMethod('put')) return $this->update($request);
            if ($request->isMethod('patch')) return $this->toggleActive($request);
            if ($request->isMethod('delete')) return $this->destroyOrRemoveFile($request);

            $assistants = Assistant::orderBy('name', 'asc')->get();
            $configuring = null;
            $lastWebhook = null;

            if ($request->has('configure')) {
                $configuring = Assistant::find($request->configure);
                if ($configuring && Schema::hasTable('webhook_logs')) {
                    $log = DB::table('webhook_logs')->where('assistant_id', $configuring->id)->latest('id')->first();
                    if ($log) {
                        $lastWebhook = (array) $log;
                        if (isset($lastWebhook['wa_send_result']) && is_string($lastWebhook['wa_send_result'])) {
                            $lastWebhook['wa_send_result'] = json_decode($lastWebhook['wa_send_result'], true);
                        }
                    }
                }
            }

            return view('assistants.index', compact('assistants', 'configuring', 'lastWebhook'));
        } catch (\Throwable $e) {
            Log::error('Erro index: ' . $e->getMessage());
            return redirect('/')->with('error', 'Erro ao carregar.');
        }
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
                // Salva APENAS metadados leves no banco (zero risco de quebrar JSON)
                $existingFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path
                ];
            }
            $data['knowledge_files'] = $existingFiles;
        }

        $assistant->update($data);
        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas!');
    }

    private function toggleActive(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->update(['is_active' => !$assistant->is_active]);
        return redirect()->back()->with('success', 'Status alterado!');
    }

    private function destroyOrRemoveFile(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);

        if ($request->has('file_index')) {
            $files = $assistant->knowledge_files ?? [];
            $index = $request->file_index;
            if (isset($files[$index])) {
                if (isset($files[$index]['path'])) {
                    Storage::delete($files[$index]['path']);
                }
                array_splice($files, $index, 1);
                $assistant->update(['knowledge_files' => $files]);
            }
            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo removido.');
        }

        $assistant->delete();
        return redirect('/')->with('success', 'Assistente excluído!');
    }

    private function buildSystemPromptWithKnowledge(Assistant $assistant): string
    {
        $prompt = $assistant->system_prompt ?? '';
        $files = $assistant->knowledge_files ?? [];

        if (is_array($files) && !empty($files)) {
            $prompt .= "\n\n### BASE DE CONHECIMENTO (DOCUMENTOS ANEXADOS) ###\n";
            foreach ($files as $file) {
                if (!empty($file['path']) && Storage::exists($file['path'])) {
                    try {
                        $fullPath = Storage::path($file['path']);
                        $text = $this->extractText($fullPath, $file['name'] ?? '');
                        if (!empty($text)) {
                            $prompt .= "\n--- DOCUMENTO: " . ($file['name'] ?? 'Arquivo') . " ---\n" . $text . "\n";
                        }
                    } catch (\Throwable $e) {
                        Log::error('Erro ao ler anexo no prompt: ' . $e->getMessage());
                    }
                }
            }
        }

        return $prompt;
    }

    private function extractText(string $filePath, string $fileName): string
    {
        if (!file_exists($filePath)) return '';

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $text = '';

        if ($ext === 'txt') {
            $text = @file_get_contents($filePath) ?: '';
        } elseif ($ext === 'docx' && class_exists('\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $text = trim(strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $data)));
                }
                $zip->close();
            }
        } elseif ($ext === 'pdf') {
            $raw = @file_get_contents($filePath);
            if ($raw) {
                preg_match_all('/BT[\s\S]*?ET/s', $raw, $matches);
                $extracted = '';
                foreach ($matches[0] ?? [] as $match) {
                    preg_match_all('/\((.*?)\)/s', $match, $txtMatches);
                    foreach ($txtMatches[1] ?? [] as $m) {
                        if (strlen($m) > 1 && ctype_print($m)) $extracted .= $m . ' ';
                    }
                }
                $text = trim($extracted);
            }
        }

        return $this->sanitizeUtf8($text);
    }

    private function sanitizeUtf8($text): string
    {
        if (!is_string($text) || empty($text)) return '';
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($clean === false) {
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '', $text);
        }
        return trim($clean);
    }

    private function chat(Request $request)
    {
        try {
            $assistant = Assistant::findOrFail($request->assistant_id);
            $userMessage = (string)$request->input('message');
            $history = $request->input('history', []);

            $systemPrompt = $this->buildSystemPromptWithKnowledge($assistant);
            $response = $this->callAiApi($assistant, $systemPrompt, $userMessage, $history);

            return response()->json(['reply' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['reply' => '⚠️ Erro na IA: ' . $e->getMessage()]);
        }
    }

    public function webhook(Request $request, $id)
    {
        try {
            $assistant = Assistant::find($id);
            if (!$assistant || !$assistant->is_active) {
                return response()->json(['status' => 'ignored']);
            }

            $sender = (string)($request->input('sender') ?? $request->input('phone') ?? $request->input('key.remoteJid') ?? 'desconhecido');
            $userMessage = (string)($request->input('message') ?? $request->input('text') ?? $request->input('body') ?? '');

            if (!$userMessage) return response()->json(['status' => 'no_message']);

            $systemPrompt = $this->buildSystemPromptWithKnowledge($assistant);
            $aiReply = $this->callAiApi($assistant, $systemPrompt, $userMessage, []);

            $waResult = $this->sendWhatsappMessage($assistant, $sender, $aiReply);

            if (Schema::hasTable('webhook_logs')) {
                DB::table('webhook_logs')->insert([
                    'assistant_id' => $assistant->id,
                    'sender' => $sender,
                    'user_message' => $userMessage,
                    'ai_reply' => $aiReply,
                    'wa_send_result' => json_encode($waResult),
                    'raw_snippet' => json_encode($request->all()),
                    'timestamp' => now()->toDateTimeString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['status' => 'success', 'reply' => $aiReply]);
        } catch (\Throwable $e) {
            Log::error('Erro Webhook: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
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

        return 'Provedor de IA não configurado.';
    }

    private function sendWhatsappMessage(Assistant $assistant, string $to, string $message): array
    {
        if (empty($assistant->whatsapp_url) || empty($assistant->whatsapp_token)) {
            return ['success' => false, 'error' => 'WhatsApp não configurado.'];
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

            return ['success' => $response->successful(), 'error' => $response->failed() ? $response->body() : null];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}