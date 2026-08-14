<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('webhook_id')) {
            return $this->webhook($request, $request->input('webhook_id'));
        }

        if ($request->isMethod('put')) {
            return $this->updateConfig($request);
        }
        if ($request->isMethod('patch')) {
            return $this->toggleStatus($request);
        }
        if ($request->isMethod('delete')) {
            return $this->destroy($request);
        }
        if ($request->isMethod('post') || $request->has('action')) {
            return $this->store($request);
        }

        if ($request->has('chat_id')) {
            $assistant = Assistant::findOrFail($request->chat_id);
            return view('assistants.chat', compact('assistant'));
        }

        $assistants = Assistant::orderBy('name', 'asc')->get();
        $configuring = $request->has('configure') ? Assistant::find($request->configure) : null;
        
        $lastWebhook = null;
        if ($configuring) {
            $logFile = "webhook_log_{$configuring->id}.json";
            if (Storage::exists($logFile)) {
                $lastWebhook = json_decode(Storage::get($logFile), true);
            }
        }

        return view('assistants.index', compact('assistants', 'configuring', 'lastWebhook'));
    }

    public function store(Request $request)
    {
        $action = $request->input('action');
        if ($action === 'test_ai') return $this->testAiConnection($request);
        if ($action === 'test_whatsapp') return $this->testWaConnection($request);
        if ($action === 'status_whatsapp') return $this->statusWaConnection($request);
        if ($action === 'disconnect_whatsapp') return $this->disconnectWaConnection($request);
        if ($action === 'chat_message') return $this->handleChatMessage($request);

        $request->validate(['name' => 'required|string|max:255']);
        
        Assistant::create([
            'name' => $request->name, 
            'is_active' => false
        ]);
        
        return redirect('/')->with('success', 'Assistente criado com sucesso (Inativo por padrão)!');
    }

    public function updateConfig(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        
        $assistant->update($request->only([
            'provider', 'model', 'system_prompt', 
            'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key',
            'whatsapp_provider', 'whatsapp_url', 'whatsapp_instance', 'whatsapp_token', 'whatsapp_verify_token'
        ]));

        if ($request->hasFile('documents')) {
            $files = $assistant->knowledge_files ?? [];
            $uploadedCount = 0;
            
            foreach ($request->file('documents') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('knowledge_bases');
                    $files[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => $file->getClientMimeType()
                    ];
                    $uploadedCount++;
                }
            }
            
            if ($uploadedCount > 0) {
                $assistant->update(['knowledge_files' => $files]);
                return redirect('/?configure=' . $assistant->id)->with('success', "{$uploadedCount} arquivo(s) anexado(s) com sucesso!");
            } else {
                return redirect('/?configure=' . $assistant->id)->with('error', 'Falha ao subir o arquivo.');
            }
        }

        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas e salvas!');
    }

    public function toggleStatus(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->update(['is_active' => !$assistant->is_active]);
        
        if ($request->has('from_config')) {
            return redirect('/?configure=' . $assistant->id)->with('success', 'Status alterado!');
        }
        return redirect('/')->with('success', 'Status atualizado!');
    }

    public function destroy(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);

        if ($request->has('file_index')) {
            $files = $assistant->knowledge_files ?? [];
            $index = $request->file_index;

            if (isset($files[$index])) {
                Storage::delete($files[$index]['path']);
                unset($files[$index]);
                $assistant->update(['knowledge_files' => array_values($files)]);
            }

            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo removido da base de conhecimento!');
        }

        $assistant->delete();
        return redirect('/')->with('success', 'Assistente removido!');
    }

    // ============================================================================
    // RECEPTOR DE WEBHOOK DO WHATSAPP
    // ============================================================================

    public function webhook(Request $request, $id = null)
    {
        if ($request->isMethod('get')) {
            return response('OK', 200);
        }

        $assistantId = $id ?? $request->input('webhook_id');
        $assistant = Assistant::find($assistantId);

        $logData = [
            'timestamp' => now()->format('d/m/Y H:i:s'),
            'status' => 'received',
            'sender' => 'Desconhecido',
            'user_message' => 'Nenhuma',
            'ai_reply' => null,
            'wa_send_result' => null,
            'error' => null
        ];

        if (!$assistant) {
            $logData['status'] = 'error';
            $logData['error'] = 'Assistente não encontrado no banco.';
            $this->saveWebhookLog($assistantId, $logData);
            return response()->json(['status' => 'not_found'], 200);
        }

        if (!$assistant->is_active) {
            $logData['status'] = 'ignored';
            $logData['error'] = 'Assistente está INATIVO no painel.';
            $this->saveWebhookLog($assistantId, $logData);
            return response()->json(['status' => 'ignored_inactive'], 200);
        }

        $fromMeRaw = $request->input('data.key.fromMe')
                  ?? $request->input('key.fromMe')
                  ?? $request->input('message.fromMe')
                  ?? $request->input('fromMe')
                  ?? false;

        $isFromMe = filter_var($fromMeRaw, FILTER_VALIDATE_BOOLEAN);

        if ($isFromMe) {
            $logData['status'] = 'ignored';
            $logData['error'] = 'Mensagem enviada pelo próprio número (fromMe=true).';
            $this->saveWebhookLog($assistantId, $logData);
            return response()->json(['status' => 'ignored_from_me'], 200);
        }

        $userMessage = $request->input('data.message.conversation')
                    ?? $request->input('data.message.extendedTextMessage.text')
                    ?? $request->input('data.body')
                    ?? $request->input('data.text')
                    ?? $request->input('message.text')
                    ?? $request->input('message.conversation')
                    ?? $request->input('message.body')
                    ?? $request->input('text')
                    ?? $request->input('body')
                    ?? $request->input('msg')
                    ?? $request->input('message');

        if (empty($userMessage) || !is_string($userMessage)) {
            $logData['status'] = 'ignored';
            $logData['error'] = 'Mensagem recebida sem texto legível.';
            $this->saveWebhookLog($assistantId, $logData);
            return response()->json(['status' => 'ignored_empty_message'], 200);
        }

        // PRIORIDADE ABSOLUTA AO REMOTEJID REAL DO WHATSAPP
        $remoteJid = $request->input('data.key.remoteJid')
                  ?? $request->input('key.remoteJid')
                  ?? $request->input('data.remoteJid')
                  ?? $request->input('remoteJid')
                  ?? $request->input('chatId')
                  ?? $request->input('data.from');

        if (is_string($remoteJid) && str_contains($remoteJid, '@g.us')) {
            $logData['status'] = 'ignored';
            $logData['error'] = 'Mensagem de grupo de WhatsApp.';
            $this->saveWebhookLog($assistantId, $logData);
            return response()->json(['status' => 'ignored_group_message'], 200);
        }

        $cleanNumber = preg_replace('/[^0-9]/', '', strtok($remoteJid, '@'));

        // Se for número do Brasil sem DDI (10 ou 11 dígitos), insere DDI 55 automaticamente
        if (strlen($cleanNumber) >= 10 && strlen($cleanNumber) <= 11 && !str_starts_with($cleanNumber, '55')) {
            $cleanNumber = '55' . $cleanNumber;
        }

        if (empty($cleanNumber) || strlen($cleanNumber) < 10) {
            $logData['status'] = 'ignored';
            $logData['error'] = "Número do remetente inválido capturado: '{$cleanNumber}'";
            $this->saveWebhookLog($assistantId, $logData);
            return response()->json(['status' => 'ignored_invalid_number'], 200);
        }

        $logData['sender'] = $cleanNumber;
        $logData['user_message'] = $userMessage;

        // Processa resposta na IA
        $aiReply = $this->processAiConversation($assistant, $userMessage);
        $logData['ai_reply'] = $aiReply;

        // Dispara mensagem no WhatsApp
        $sendResult = $this->sendWaMessageDetailed($assistant, $cleanNumber, $aiReply);
        $logData['wa_send_result'] = $sendResult;
        $logData['status'] = $sendResult['success'] ? 'success' : 'failed_to_send';

        $this->saveWebhookLog($assistantId, $logData);

        return response()->json([
            'status' => $sendResult['success'] ? 'success' : 'failed_to_send',
            'recipient' => $cleanNumber,
            'reply' => $aiReply,
            'send_details' => $sendResult
        ], 200);
    }

    private function saveWebhookLog($assistantId, $data)
    {
        if ($assistantId) {
            Storage::put("webhook_log_{$assistantId}.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    private function sendWaMessageDetailed($assistant, $number, $text)
    {
        $url = rtrim($assistant->whatsapp_url, '/');
        $instance = trim($assistant->whatsapp_instance);
        $token = trim($assistant->whatsapp_token);
        $provider = $assistant->whatsapp_provider;

        if (empty($url) || empty($token)) {
            return ['success' => false, 'error' => 'URL ou Token do WhatsApp não preenchidos nas configurações.'];
        }

        $headers = [
            'token' => $token,
            'apikey' => $token,
            'Client-Token' => $token,
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        try {
            if ($provider === 'uazapi') {
                $candidates = [
                    ['path' => "/send/text", 'body' => ['number' => $number, 'text' => $text]],
                    ['path' => "/send/text?token={$token}", 'body' => ['number' => $number, 'text' => $text]],
                    ['path' => "/send/text", 'body' => ['chatId' => "{$number}@s.whatsapp.net", 'text' => $text]],
                    ['path' => "/send/text", 'body' => ['number' => $number, 'message' => $text]],
                    ['path' => "/message/sendText/{$instance}", 'body' => ['number' => $number, 'text' => $text]]
                ];

                $attempts = [];
                foreach ($candidates as $cand) {
                    $res = Http::withHeaders($headers)->timeout(12)->post($url . $cand['path'], $cand['body']);
                    if ($res->successful()) {
                        return ['success' => true, 'path_used' => $cand['path'], 'response' => $res->json()];
                    }
                    $attempts[] = "{$cand['path']} (Status {$res->status()}): " . substr($res->body(), 0, 150);
                }

                return ['success' => false, 'error' => 'UaZapi recusou todas as rotas de envio.', 'attempts' => $attempts];
            } elseif ($provider === 'evolution') {
                $res = Http::withHeaders($headers)->timeout(12)->post("{$url}/message/sendText/{$instance}", [
                    'number' => $number,
                    'text' => $text
                ]);
                if ($res->successful()) return ['success' => true, 'response' => $res->json()];
                return ['success' => false, 'error' => "Evolution API Status {$res->status()}: " . $res->body()];
            }

            return ['success' => false, 'error' => 'Provedor não suportado.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Exceção de rede: ' . $e->getMessage()];
        }
    }

    private function handleChatMessage(Request $request)
    {
        $assistantId = $request->input('assistant_id');
        $userMessage = $request->input('message');
        $history = $request->input('history', []);

        if (empty($assistantId) || empty($userMessage)) {
            return response()->json(['success' => false, 'reply' => 'Mensagem ou assistente não informado.']);
        }

        $assistant = Assistant::find($assistantId);
        if (!$assistant) {
            return response()->json(['success' => false, 'reply' => 'Assistente não encontrado.']);
        }

        $reply = $this->processAiConversation($assistant, $userMessage, $history);
        return response()->json(['success' => true, 'reply' => $reply]);
    }

    private function processAiConversation($assistant, $userMessage, $history = [])
    {
        $provider = $assistant->provider ?? 'openai';
        $model = $assistant->model ?? 'gpt-4o-mini';
        
        if ($provider === 'openai' && !str_starts_with($model, 'gpt-')) {
            $model = 'gpt-4o-mini';
        } elseif ($provider === 'gemini' && !str_starts_with($model, 'gemini-')) {
            $model = 'gemini-1.5-flash';
        } elseif ($provider === 'anthropic' && !str_starts_with($model, 'claude-')) {
            $model = 'claude-3-haiku';
        } elseif ($provider === 'grok' && !str_starts_with($model, 'grok-')) {
            $model = 'grok-2-mini';
        }

        $systemInstruction = $assistant->system_prompt ?? "Você é um assistente virtual prestativo.";

        if (!empty($assistant->knowledge_files) && is_array($assistant->knowledge_files)) {
            $knowledgeText = "";
            foreach ($assistant->knowledge_files as $file) {
                if (isset($file['path']) && Storage::exists($file['path'])) {
                    $content = @Storage::get($file['path']);
                    if ($content) {
                        $cleanContent = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                        $knowledgeText .= "\n--- Documento: " . ($file['name'] ?? 'Arquivo') . " ---\n" . substr($cleanContent, 0, 4000) . "\n";
                    }
                }
            }
            if (!empty($knowledgeText)) {
                $systemInstruction .= "\n\n[BASE DE CONHECIMENTO DISPONÍVEL DO USUÁRIO]:\n" . $knowledgeText;
            }
        }

        $apiKey = match($provider) {
            'openai' => $assistant->openai_api_key,
            'gemini' => $assistant->gemini_api_key,
            'anthropic' => $assistant->anthropic_api_key,
            'grok' => $assistant->grok_api_key,
            default => null
        };

        if (empty($apiKey)) {
            return "⚠️ A chave de API para o provedor '" . strtoupper($provider) . "' não foi configurada neste assistente.";
        }

        try {
            if ($provider === 'openai' || $provider === 'grok') {
                $endpoint = ($provider === 'openai') ? 'https://api.openai.com/v1/chat/completions' : 'https://api.x.ai/v1/chat/completions';
                
                $formattedMessages = [
                    ['role' => 'system', 'content' => $systemInstruction]
                ];

                if (is_array($history) && count($history) > 0) {
                    foreach ($history as $h) {
                        if (isset($h['role'], $h['content']) && in_array($h['role'], ['user', 'assistant'])) {
                            $formattedMessages[] = [
                                'role' => $h['role'],
                                'content' => (string) $h['content']
                            ];
                        }
                    }
                } else {
                    $formattedMessages[] = ['role' => 'user', 'content' => $userMessage];
                }

                $res = Http::withToken($apiKey)->timeout(30)->post($endpoint, [
                    'model' => $model,
                    'messages' => $formattedMessages,
                    'temperature' => 0.7
                ]);

                if ($res->successful()) {
                    return $res->json()['choices'][0]['message']['content'] ?? "Sem resposta da IA.";
                }
                return "Erro na IA ({$res->status()}): " . ($res->json()['error']['message'] ?? 'Falha na resposta.');
            }

            if ($provider === 'gemini') {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                
                $contents = [];
                if (is_array($history) && count($history) > 0) {
                    foreach ($history as $h) {
                        if (isset($h['role'], $h['content'])) {
                            $role = ($h['role'] === 'assistant') ? 'model' : 'user';
                            $contents[] = [
                                'role' => $role,
                                'parts' => [['text' => (string) $h['content']]]
                            ];
                        }
                    }
                } else {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => $userMessage]]
                    ];
                }

                $res = Http::timeout(30)->post($endpoint, [
                    'system_instruction' => [
                        'parts' => [['text' => $systemInstruction]]
                    ],
                    'contents' => $contents
                ]);

                if ($res->successful()) {
                    return $res->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Sem resposta da IA.";
                }
                return "Erro no Gemini ({$res->status()}): Verifique sua API Key.";
            }

            if ($provider === 'anthropic') {
                $formattedMessages = [];
                if (is_array($history) && count($history) > 0) {
                    foreach ($history as $h) {
                        if (isset($h['role'], $h['content']) && in_array($h['role'], ['user', 'assistant'])) {
                            $formattedMessages[] = [
                                'role' => $h['role'],
                                'content' => (string) $h['content']
                            ];
                        }
                    }
                } else {
                    $formattedMessages[] = ['role' => 'user', 'content' => $userMessage];
                }

                $res = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01'
                ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 1024,
                    'system' => $systemInstruction,
                    'messages' => $formattedMessages
                ]);

                if ($res->successful()) {
                    return $res->json()['content'][0]['text'] ?? "Sem resposta da IA.";
                }
                return "Erro no Claude ({$res->status()}): " . ($res->json()['error']['message'] ?? 'Falha.');
            }

            return "Provedor não suportado.";
        } catch (\Exception $e) {
            return "Erro ao processar conversa: " . $e->getMessage();
        }
    }

    private function testAiConnection(Request $request)
    {
        $provider = $request->input('provider');
        $apiKey = $request->input('api_key');

        if (empty($apiKey)) return response()->json(['success' => false, 'message' => 'Informe a API Key do provedor.']);

        try {
            if ($provider === 'openai') {
                $res = Http::withToken($apiKey)->timeout(8)->get('https://api.openai.com/v1/models');
            } elseif ($provider === 'gemini') {
                $res = Http::timeout(8)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            } elseif ($provider === 'anthropic') {
                $res = Http::withHeaders(['x-api-key' => $apiKey, 'anthropic-version' => '2023-06-01'])->timeout(8)->get('https://api.anthropic.com/v1/models');
            } elseif ($provider === 'grok') {
                $res = Http::withToken($apiKey)->timeout(8)->get('https://api.x.ai/v1/models');
            } else {
                return response()->json(['success' => false, 'message' => 'Provedor de IA inválido.']);
            }

            if ($res->successful()) {
                return response()->json(['success' => true, 'message' => 'Conexão com IA estabelecida com sucesso!']);
            } else {
                $errData = $res->json();
                $msg = is_array($errData) ? ($errData['error']['message'] ?? $errData['message'] ?? "Status {$res->status()}") : "Status {$res->status()}";
                return response()->json(['success' => false, 'message' => "Falha na IA ({$res->status()}): {$msg}"]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro de comunicação com a API de IA: ' . $e->getMessage()]);
        }
    }

    private function statusWaConnection(Request $request)
    {
        $provider = $request->input('provider');
        $url = rtrim($request->input('url'), '/');
        $instance = trim($request->input('instance'));
        $token = trim($request->input('token'));

        if (empty($provider) || empty($url) || empty($token)) return response()->json(['connected' => false]);

        try {
            $headers = [
                'token' => $token, 
                'apikey' => $token, 
                'Client-Token' => $token, 
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            if ($provider === 'uazapi') {
                $res = Http::withHeaders($headers)->timeout(5)->post("{$url}/instance/connect", ['instanceName' => $instance]);
                if (!$res->successful() || $res->status() === 400) {
                    $res = Http::withHeaders($headers)->timeout(5)->post("{$url}/instance/connect", (object)[]);
                }
                if (!$res->successful()) {
                    $res = Http::withHeaders($headers)->timeout(5)->get("{$url}/instance/connect/{$instance}");
                }
                if (!$res->successful()) {
                    $res = Http::withHeaders($headers)->timeout(5)->get("{$url}/instance/status");
                }

                if ($res->successful()) {
                    $data = $res->json();
                    $inst = $data['instance'] ?? $data;
                    $status = strtolower($inst['status'] ?? $inst['state'] ?? $data['status'] ?? $data['state'] ?? '');
                    if (in_array($status, ['open', 'connected', 'connecting_connected']) || ($data['connected'] ?? false) === true) {
                        return response()->json(['connected' => true]);
                    }
                }
            } elseif ($provider === 'evolution') {
                $res = Http::withHeaders($headers)->timeout(5)->get("{$url}/instance/connect/{$instance}");
                if ($res->successful()) {
                    $status = strtolower($res->json()['instance']['state'] ?? '');
                    return response()->json(['connected' => in_array($status, ['open', 'connected'])]);
                }
            }
            return response()->json(['connected' => false]);
        } catch (\Exception $e) {
            return response()->json(['connected' => false]);
        }
    }

    private function disconnectWaConnection(Request $request)
    {
        $provider = $request->input('provider');
        $url = rtrim($request->input('url'), '/');
        $instance = trim($request->input('instance'));
        $token = trim($request->input('token'));

        if (empty($provider) || empty($url) || empty($token)) {
            return response()->json(['success' => false, 'message' => 'Parâmetros incompletos.']);
        }

        try {
            $headers = [
                'token' => $token, 
                'apikey' => $token, 
                'Client-Token' => $token, 
                'Authorization' => "Bearer {$token}", 
                'Content-Type' => 'application/json', 
                'Accept' => 'application/json'
            ];

            if ($provider === 'uazapi' || $provider === 'evolution') {
                $candidates = [
                    ['method' => 'POST',   'path' => "/instance/disconnect", 'body' => ['instanceName' => $instance]],
                    ['method' => 'POST',   'path' => "/instance/disconnect", 'body' => (object)[]],
                    ['method' => 'DELETE', 'path' => "/instance/disconnect"],
                    ['method' => 'DELETE', 'path' => "/instance/logout/{$instance}"],
                    ['method' => 'DELETE', 'path' => "/instance/logout"],
                    ['method' => 'POST',   'path' => "/instance/logout", 'body' => ['instanceName' => $instance]],
                    ['method' => 'POST',   'path' => "/instance/logout", 'body' => (object)[]]
                ];

                $errors = [];
                foreach ($candidates as $cand) {
                    $req = Http::withHeaders($headers)->timeout(8);
                    $res = ($cand['method'] === 'DELETE') ? $req->delete($url . $cand['path']) : $req->post($url . $cand['path'], $cand['body']);

                    if ($res->successful()) {
                        return response()->json(['success' => true]);
                    }
                    $errors[] = "{$cand['method']} {$cand['path']}: {$res->status()}";
                }
                
                return response()->json(['success' => false, 'message' => "Servidor recusou desconectar: " . implode(' | ', $errors)]);
            }

            return response()->json(['success' => false, 'message' => 'Provedor não suportado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro de conexão com a API.']);
        }
    }

    private function testWaConnection(Request $request)
    {
        $provider = $request->input('provider');
        $url = rtrim($request->input('url'), '/');
        $instance = trim($request->input('instance'));
        $token = trim($request->input('token'));

        if (empty($provider) || empty($instance) || empty($token)) {
            return response()->json(['success' => false, 'message' => 'Preencha URL, Instância e Token para testar a conexão.']);
        }

        try {
            if ($provider === 'uazapi') {
                $headers = [
                    'token' => $token, 
                    'apikey' => $token, 
                    'Client-Token' => $token, 
                    'Authorization' => "Bearer {$token}", 
                    'Content-Type' => 'application/json', 
                    'Accept' => 'application/json'
                ];

                $res = Http::withHeaders($headers)->timeout(10)->post("{$url}/instance/connect", ['instanceName' => $instance]);

                if ($res->status() === 401) {
                    return response()->json([
                        'success' => false,
                        'message' => '🔑 Token Inválido (401): A UaZapi recusou este Token. Copie o Token atualizado da sua instância no painel da UaZapi, cole no campo "Instance Token" e clique em "Salvar Configurações".'
                    ]);
                }

                if (!$res->successful()) {
                    $res = Http::withHeaders($headers)->timeout(10)->post("{$url}/instance/connect", (object)[]);
                }

                if ($res->successful()) {
                    $data = $res->json();
                    $inst = $data['instance'] ?? $data;
                    $status = strtolower($inst['status'] ?? $inst['state'] ?? $data['status'] ?? $data['state'] ?? '');

                    if (in_array($status, ['open', 'connected', 'connecting_connected']) || ($data['connected'] ?? false) === true) {
                        return response()->json(['success' => true, 'connected' => true, 'message' => '✅ WhatsApp pareado com sucesso!']);
                    }

                    $qr = $this->extractQrCode($data);
                    if ($qr) {
                        return response()->json(['success' => true, 'connected' => false, 'qr' => $qr, 'message' => 'Abra seu WhatsApp > Aparelhos Conectados e escaneie a imagem abaixo:']);
                    }

                    return response()->json(['success' => true, 'connected' => false, 'qr' => null, 'message' => "Instância ligada (Status: {$status}). Gerando QR Code..."]);
                }

                $err = $res->json();
                $errMsg = is_array($err) ? ($err['message'] ?? $err['error'] ?? 'Erro no servidor') : $res->body();
                return response()->json(['success' => false, 'message' => "Erro {$res->status()}: {$errMsg}"]);
            }

            if ($provider === 'evolution') {
                $headers = ['apikey' => $token, 'Authorization' => "Bearer {$token}"];
                $res = Http::withHeaders($headers)->timeout(10)->get("{$url}/instance/connect/{$instance}");

                if ($res->successful()) {
                    $data = $res->json();
                    $status = strtolower($data['instance']['state'] ?? $data['state'] ?? '');
                    
                    if ($status === 'open' || $status === 'connected') return response()->json(['success' => true, 'connected' => true, 'message' => '✅ WhatsApp conectado!']);

                    $qr = $this->extractQrCode($data);
                    if ($qr) return response()->json(['success' => true, 'connected' => false, 'qr' => $qr, 'message' => 'Escaneie a imagem abaixo:']);

                    return response()->json(['success' => true, 'connected' => false, 'qr' => null, 'message' => "Aguardando imagem da Evolution API..."]);
                }
                return response()->json(['success' => false, 'message' => "Erro {$res->status()} na Evolution API."]);
            }

            return response()->json(['success' => true, 'message' => "Integração em desenvolvimento."]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro de comunicação de rede com o servidor.']);
        }
    }

    private function extractQrCode($data)
    {
        if (!is_array($data)) return null;

        $candidates = [$data['instance']['qrcode'] ?? null, $data['instance']['qr'] ?? null, $data['qrcode'] ?? null, $data['base64'] ?? null, $data['qr'] ?? null, $data['code'] ?? null];

        foreach ($candidates as $cand) {
            if (is_string($cand) && strlen(trim($cand)) > 30) return $this->formatBase64Image($cand);
        }

        $recursive = $this->findQrCodeInArray($data);
        if ($recursive) return $this->formatBase64Image($recursive);

        return null;
    }

    private function formatBase64Image($str) {
        $str = trim($str);
        if (!str_starts_with($str, 'data:image')) {
            $str = str_contains($str, 'base64,') ? 'data:image/png;' . substr($str, strpos($str, 'base64,')) : 'data:image/png;base64,' . $str;
        }
        return $str;
    }

    private function findQrCodeInArray($array) {
        if (!is_array($array)) return null;
        foreach ($array as $key => $value) {
            if (is_string($value) && strlen(trim($value)) > 30 && (str_starts_with($value, 'data:image') || str_contains($key, 'qr') || str_contains($key, 'code') || str_contains($key, 'base64'))) {
                return $value;
            } elseif (is_array($value)) {
                $found = $this->findQrCodeInArray($value);
                if ($found) return $found;
            }
        }
        return null;
    }
}