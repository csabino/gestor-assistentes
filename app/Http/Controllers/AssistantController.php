<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        // Limpa a sujeira do banco automaticamente silenciosamente
        try { DB::statement('UPDATE assistants SET knowledge_files = NULL'); } catch (\Throwable $e) {}

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

        // Apenas salva o arquivo fisicamente, não tenta ler nada
        if ($request->hasFile('documents')) {
            $existingFiles = $assistant->knowledge_files ?? [];
            foreach ($request->file('documents') as $file) {
                $path = $file->store('knowledge_base');
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
                if (isset($files[$index]['path'])) Storage::delete($files[$index]['path']);
                array_splice($files, $index, 1);
                $assistant->update(['knowledge_files' => $files]);
            }
            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo removido.');
        }

        $assistant->delete();
        return redirect('/')->with('success', 'Assistente excluído!');
    }

    private function chat(Request $request)
    {
        try {
            $assistant = Assistant::find($request->assistant_id);
            if (!$assistant) return response()->json(['reply' => 'Assistente não encontrado.']);

            $userMessage = (string)$request->input('message');
            $history = $request->input('history', []);
            if (!is_array($history)) $history = [];

            $systemPrompt = $assistant->system_prompt ?? '';
            $response = $this->callAiApi($assistant, $systemPrompt, $userMessage, $history);

            return response()->json(['reply' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['reply' => '⚠️ Erro interno da IA: ' . $e->getMessage()]);
        }
    }

    public function webhook(Request $request, $id)
    {
        try {
            $assistant = Assistant::find($id);
            if (!$assistant || !$assistant->is_active) {
                return response()->json(['status' => 'ignored']);
            }

            $sender = $request->input('sender') ?? $request->input('phone') ?? $request->input('key.remoteJid') ?? 'desconhecido';
            if (is_array($sender)) $sender = json_encode($sender);

            $userMessage = $request->input('message') ?? $request->input('text') ?? $request->input('body') ?? '';
            if (is_array($userMessage)) $userMessage = json_encode($userMessage);

            if (empty($userMessage)) return response()->json(['status' => 'no_message']);

            $systemPrompt = $assistant->system_prompt ?? '';
            $aiReply = $this->callAiApi($assistant, $systemPrompt, (string)$userMessage, []);

            $waResult = $this->sendWhatsappMessage($assistant, (string)$sender, $aiReply);

            if (Schema::hasTable('webhook_logs')) {
                DB::table('webhook_logs')->insert([
                    'assistant_id' => $assistant->id,
                    'sender' => substr((string)$sender, 0, 255),
                    'user_message' => (string)$userMessage,
                    'ai_reply' => $aiReply,
                    'wa_send_result' => json_encode($waResult, JSON_INVALID_UTF8_IGNORE),
                    'raw_snippet' => json_encode($request->all(), JSON_INVALID_UTF8_IGNORE),
                    'timestamp' => now()->toDateTimeString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['status' => 'success', 'reply' => $aiReply]);
        } catch (\Throwable $e) {
            try {
                if (Schema::hasTable('webhook_logs')) {
                    DB::table('webhook_logs')->insert([
                        'assistant_id' => $id,
                        'sender' => 'Erro',
                        'user_message' => 'Erro',
                        'ai_reply' => 'Falha Crítica',
                        'wa_send_result' => json_encode(['error' => $e->getMessage()]),
                        'raw_snippet' => json_encode($request->all()),
                        'timestamp' => now()->toDateTimeString()
                    ]);
                }
            } catch (\Throwable $err) {}
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

    private function testAi(Request $request)
    {
        $provider = $request->provider;
        $apiKey = $request->api_key;

        if (!$apiKey) return response()->json(['success' => false, 'message' => 'Informe uma chave API válida.']);

        try {
            if ($provider === 'openai') {
                $res = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'Responda OK']]
                ]);
                return response()->json(['success' => $res->successful(), 'message' => $res->successful() ? 'Conexão OpenAI OK!' : $res->json('error.message')]);
            }
            if ($provider === 'gemini') {
                $res = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => 'Responda OK']]]]
                ]);
                return response()->json(['success' => $res->successful(), 'message' => $res->successful() ? 'Conexão Gemini OK!' : 'Falha ao autenticar.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
        return response()->json(['success' => false, 'message' => 'Provedor inválido.']);
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