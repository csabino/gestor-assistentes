<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

class AssistantController extends Controller
{
    private function configureTimezone()
    {
        date_default_timezone_set('America/Sao_Paulo');
    }

    private function ensureWebhookLogTableExists()
    {
        if (!Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assistant_id');
                $table->string('sender')->nullable();
                $table->text('user_message')->nullable();
                $table->text('ai_reply')->nullable();
                $table->longText('wa_send_result')->nullable();
                $table->longText('raw_snippet')->nullable();
                $table->string('timestamp')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensureAssistantColumnsExist()
    {
        if (Schema::hasTable('assistants')) {
            if (!Schema::hasColumn('assistants', 'context_limit')) {
                Schema::table('assistants', function (Blueprint $table) {
                    $table->integer('context_limit')->default(12)->after('model');
                    $table->longText('lead_fields')->nullable()->after('context_limit');
                });
            }
        }
    }

    private function ensureChatMessagesTableExists()
    {
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assistant_id');
                $table->string('phone_number')->index();
                $table->string('role');
                $table->text('content');
                $table->timestamps();
            });
        }
    }

    private function ensureDepartmentTablesExist()
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('department_members')) {
            Schema::create('department_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('department_id');
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        }
    }

    public function index(Request $request)
    {
        $this->configureTimezone();
        $this->ensureWebhookLogTableExists();
        $this->ensureAssistantColumnsExist();
        $this->ensureChatMessagesTableExists();
        $this->ensureDepartmentTablesExist();

        $currentView = $request->input('view', 'robots');

        if ($request->has('chat_id')) {
            $assistant = Assistant::findOrFail($request->chat_id);
            return view('assistants.chat', compact('assistant'));
        }

        if ($request->isMethod('post') && $request->input('action') === 'store_agent') return $this->storeAgent($request);
        if ($request->isMethod('post') && $request->input('action') === 'store_department') return $this->storeDepartment($request);
        if ($request->isMethod('post') && $request->input('action') === 'chat') return $this->chat($request);
        if ($request->isMethod('post') && $request->input('action') === 'test_ai') return $this->testAi($request);
        
        if ($request->isMethod('post') && $request->input('action') === 'status_whatsapp') return $this->checkWhatsappStatus($request, false);
        if ($request->isMethod('post') && $request->input('action') === 'test_whatsapp') return $this->checkWhatsappStatus($request, true);
        if ($request->isMethod('post') && $request->input('action') === 'disconnect_whatsapp') return $this->disconnectWhatsapp($request);
        
        if ($request->isMethod('post') && $request->input('action') === 'map_site') return $this->mapSite($request);
        if ($request->isMethod('post') && $request->input('action') === 'scrape_single_url') return $this->scrapeSingleUrl($request);

        if ($request->isMethod('post')) return $this->store($request);
        if ($request->isMethod('put')) return $this->update($request);
        if ($request->isMethod('patch')) return $this->toggleActive($request);
        if ($request->isMethod('delete')) return $this->destroyOrRemoveFile($request);

        $assistants = Assistant::orderBy('name', 'asc')->get();
        $departments = DB::table('departments')->get();
        $agents = DB::table('department_members')->get();

        $configuring = null;
        $lastWebhook = null;
        $conversationsAssistant = null;
        $conversationThreads = [];
        $activeThreadMessages = [];
        $activePhone = $request->input('phone');

        if ($request->has('conversations_id')) {
            $conversationsAssistant = Assistant::find($request->conversations_id);
            if ($conversationsAssistant) {
                $conversationThreads = DB::table('chat_messages')
                    ->where('assistant_id', $conversationsAssistant->id)
                    ->select('phone_number', DB::raw('MAX(created_at) as last_activity'), DB::raw('COUNT(id) as total_messages'))
                    ->groupBy('phone_number')
                    ->orderBy('last_activity', 'desc')
                    ->get();

                if (!$activePhone && count($conversationThreads) > 0) {
                    $activePhone = $conversationThreads[0]->phone_number;
                }

                if ($activePhone) {
                    $activeThreadMessages = DB::table('chat_messages')
                        ->where('assistant_id', $conversationsAssistant->id)
                        ->where('phone_number', $activePhone)
                        ->orderBy('id', 'asc')
                        ->get();
                }
            }
        }

        if ($request->has('configure')) {
            $configuring = Assistant::find($request->configure);
            if ($configuring) {
                if (is_string($configuring->lead_fields)) {
                    $configuring->lead_fields = json_decode($configuring->lead_fields, true);
                }
                if (!is_array($configuring->lead_fields)) {
                    $configuring->lead_fields = [];
                }

                $log = DB::table('webhook_logs')->where('assistant_id', $configuring->id)->latest('id')->first();
                if ($log) {
                    $lastWebhook = (array) $log;
                    if (isset($lastWebhook['wa_send_result']) && is_string($lastWebhook['wa_send_result'])) {
                        $lastWebhook['wa_send_result'] = json_decode($lastWebhook['wa_send_result'], true);
                    }
                }
            }
        }

        return view('assistants.index', compact(
            'assistants', 'configuring', 'lastWebhook',
            'conversationsAssistant', 'conversationThreads', 'activeThreadMessages', 'activePhone', 'currentView',
            'departments', 'agents'
        ));
    }

private function checkWhatsappStatus(Request $request, $isTest = false)
    {
        $assistantId = $request->input('assistant_id');
        $assistant = $assistantId ? Assistant::find($assistantId) : null;

        $baseUrl = rtrim($request->input('url') ?? ($assistant->whatsapp_url ?? ''), '/');
        $token = trim($request->input('token') ?? ($assistant->whatsapp_token ?? ''));
        $instance = trim($request->input('instance') ?? ($assistant->whatsapp_instance ?? ''));
        $provider = $request->input('provider') ?? ($assistant->whatsapp_provider ?? '');

        if (!$baseUrl || !$token) {
            return response()->json(['connected' => false, 'success' => false, 'message' => 'Credenciais incompletas.']);
        }

        try {
            $headers = [
                'token' => $token,
                'Client-Token' => $token,
                'client-token' => $token,
                'apikey' => $token,
                'Content-Type' => 'application/json'
            ];

            $params = array_filter([
                'token' => $token,
                'instance' => $instance
            ]);

            $statusPaths = [
                '/instance/connectionState/' . $instance,
                '/instance/connectionState',
                '/instance/status/' . $instance,
                '/instance/status'
            ];

            $connected = false;

            foreach ($statusPaths as $path) {
                $url = $baseUrl . $path;
                $res = Http::withHeaders($headers)->get($url, $params);
                if (!$res->successful()) {
                    $res = Http::withHeaders($headers)->post($url, $params);
                }

                if ($res->successful()) {
                    $json = $res->json();
                    if (is_array($json)) {
                        if (
                            (!empty($json['connected']) && $json['connected'] === true) ||
                            (!empty($json['instance']['connected']) && $json['instance']['connected'] === true)
                        ) {
                            $connected = true;
                            break;
                        }

                        $state = $json['instance']['state'] 
                              ?? $json['instance']['status'] 
                              ?? $json['state'] 
                              ?? $json['status'] 
                              ?? $json['connectionStatus'] 
                              ?? null;

                        if (is_array($state)) {
                            $state = $state['state'] ?? $state['status'] ?? null;
                        }

                        if (is_string($state)) {
                            $stateClean = strtolower(trim($state));
                            if (in_array($stateClean, ['open', 'connected', 'conectado', 'connecting_online', 'pair', 'paired', 'working', 'online'])) {
                                $connected = true;
                                break;
                            }
                        }
                    }
                }
            }

            if ($connected) {
                return response()->json(['connected' => true, 'success' => true, 'message' => 'WhatsApp conectado!']);
            }

            if ($isTest) {
                $qrPaths = [
                    '/instance/connect/' . $instance,
                    '/instance/connect',
                    '/instance/qr/' . $instance,
                    '/instance/qr'
                ];

                foreach ($qrPaths as $path) {
                    $url = $baseUrl . $path;
                    $res = Http::withHeaders($headers)->post($url, $params);
                    if (!$res->successful()) {
                        $res = Http::withHeaders($headers)->get($url, $params);
                    }

                    if ($res->successful()) {
                        $json = $res->json();
                        if (is_array($json)) {
                            $qr = $json['qrcode'] 
                               ?? $json['base64'] 
                               ?? $json['qr'] 
                               ?? $json['code'] 
                               ?? ($json['data']['qrcode'] ?? null)
                               ?? ($json['data']['base64'] ?? null)
                               ?? ($json['instance']['qrcode'] ?? null);

                            if (is_array($qr)) {
                                $qr = $qr['base64'] ?? $qr['qrcode'] ?? $qr['code'] ?? null;
                            }

                            if (is_string($qr) && !empty($qr)) {
                                if (!str_starts_with($qr, 'data:image')) {
                                    $qr = 'data:image/png;base64,' . $qr;
                                }
                                return response()->json([
                                    'connected' => false, 
                                    'success' => true, 
                                    'qr' => $qr, 
                                    'message' => 'Escaneie o QR Code no seu celular.'
                                ]);
                            }
                        }
                    }
                }

                // Se o QR Code sumiu após o escaneamento, avisa o front-end que conectou (sem erro e sem precisar de F5)
                return response()->json([
                    'connected' => true, 
                    'success' => true, 
                    'message' => 'WhatsApp conectado com sucesso!'
                ]);
            }

            return response()->json(['connected' => false, 'success' => true, 'message' => 'WhatsApp desconectado.']);

        } catch (\Throwable $e) {
            Log::error("Erro checando status WhatsApp: " . $e->getMessage());
            return response()->json(['connected' => false, 'success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
    }

    private function disconnectWhatsapp(Request $request)
    {
        $assistantId = $request->input('assistant_id');
        $assistant = $assistantId ? Assistant::find($assistantId) : null;

        $baseUrl = rtrim($request->input('url') ?? ($assistant->whatsapp_url ?? ''), '/');
        $token = trim($request->input('token') ?? ($assistant->whatsapp_token ?? ''));
        $instance = trim($request->input('instance') ?? ($assistant->whatsapp_instance ?? ''));
        $provider = $request->input('provider') ?? ($assistant->whatsapp_provider ?? '');

        if ($baseUrl && $token) {
            try {
                $headers = [
                    'token' => $token,
                    'Client-Token' => $token,
                    'client-token' => $token,
                    'apikey' => $token,
                    'Content-Type' => 'application/json'
                ];

                $payload = array_filter([
                    'token' => $token,
                    'instance' => $instance
                ]);

                if (str_contains($baseUrl, 'uazapi.com') || $provider === 'uazapi') {
                    $response = Http::withHeaders($headers)->post($baseUrl . '/instance/disconnect', $payload);

                    if (!$response->successful()) {
                        $response = Http::withHeaders($headers)->post($baseUrl . '/instance/logout', $payload);
                    }
                } else if ($provider === 'evolution') {
                    Http::withHeaders($headers)->delete($baseUrl . '/instance/logout/' . $instance);
                }
            } catch (\Throwable $e) {
                Log::error("Erro ao desconectar WhatsApp: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'connected' => false, 'message' => 'Sessão encerrada com sucesso.']);
    }

    private function mapSite(Request $request)
    {
        $url = trim($request->input('website_url'));
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        $domain = parse_url($url, PHP_URL_HOST);
        if (!$domain) return response()->json(['success' => false, 'message' => 'URL inválida.']);
        
        $domainStr = str_replace('www.', '', $domain);

        $content = $this->fetchContentFromUrl($url);
        if (!$content) {
            return response()->json(['success' => false, 'message' => 'Falha ao acessar o site inicial. Ele pode estar bloqueando a extração.']);
        }

        $links = [$url];
        preg_match_all('/\[[^\]]*\]\((https?:\/\/[^\)]+)\)/i', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $link) {
                $link = explode('?', $link)[0];
                $link = explode('#', $link)[0];
                $link = rtrim($link, '/');
                $linkDomain = parse_url($link, PHP_URL_HOST);
                
                if ($linkDomain) {
                    $linkDomainStr = str_replace('www.', '', $linkDomain);
                    if (str_ends_with($linkDomainStr, $domainStr)) {
                        if (!preg_match('/\.(jpg|jpeg|png|gif|pdf|zip|rar|mp4|mp3|css|js|svg|webp|doc|docx)$/i', $link)) {
                            if (!in_array($link, $links)) {
                                $links[] = $link;
                            }
                        }
                    }
                }
            }
        }
        
        $links = array_slice($links, 0, 150);
        return response()->json(['success' => true, 'urls' => array_values($links)]);
    }

    private function scrapeSingleUrl(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $url = trim($request->input('website_url'));

        $content = $this->fetchContentFromUrl($url);
        
        if ($content) {
            $files = is_array($assistant->knowledge_files) ? $assistant->knowledge_files : [];
            
            $exists = false;
            foreach ($files as &$f) {
                if (($f['name'] ?? '') === '🌐 ' . $url) {
                    $f['content'] = $content;
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $files[] = [
                    'name' => '🌐 ' . $url,
                    'path' => null,
                    'content' => $content
                ];
            }
            
            $assistant->forceFill(['knowledge_files' => array_values($files)])->save();
            return response()->json(['success' => true, 'url' => $url]);
        }

        return response()->json(['success' => false, 'message' => 'Sem conteúdo na página.']);
    }

    private function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        DB::table('departments')->insert([
            'name' => trim($request->name),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect('/?view=equipe')->with('success', 'Departamento criado com sucesso!');
    }

    private function storeAgent(Request $request)
    {
        $departmentId = (int) $request->input('department_id');
        $email = strtolower(trim((string)$request->input('email')));
        $name = trim((string)$request->input('name'));

        if (!$departmentId || !$email || !$name) {
            return redirect('/?view=equipe')->with('error', 'Preencha todos os campos do agente.');
        }

        $duplicate = DB::table('department_members')
            ->where('department_id', $departmentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect('/?view=equipe')->with('error', "Operação Bloqueada: O e-mail '{$email}' já está cadastrado para o agente '{$duplicate->name}' neste departamento.");
        }

        DB::table('department_members')->insert([
            'department_id' => $departmentId,
            'name' => $name,
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/?view=equipe')->with('success', "Agente '{$name}' adicionado com sucesso!");
    }

    private function store(Request $request)
    {
        $this->configureTimezone();
        $request->validate(['name' => 'required|string|max:255']);
        $assistant = new Assistant();
        $assistant->forceFill([
            'name' => $request->name,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'context_limit' => 12,
            'system_prompt' => 'Você é um assistente virtual prestativo.',
            'is_active' => true,
        ])->save();
        
        return redirect('/?configure=' . $assistant->id)->with('success', 'Assistente criado com sucesso!');
    }

    private function update(Request $request)
    {
        $this->configureTimezone();
        $assistant = Assistant::findOrFail($request->assistant_id);

        $data = $request->only([
            'system_prompt', 'provider', 'model', 'context_limit',
            'whatsapp_provider', 'whatsapp_url', 'whatsapp_instance', 'whatsapp_token', 'whatsapp_verify_token'
        ]);

        if ($request->has('lead_fields')) {
            $fields = $request->input('lead_fields');
            $cleanFields = [];
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if (!empty($field['name']) && !empty($field['label'])) {
                        $cleanFields[] = $field;
                    }
                }
            }
            $data['lead_fields'] = json_encode($cleanFields, JSON_UNESCAPED_UNICODE);
        } else {
            if ($request->has('system_prompt')) {
                $data['lead_fields'] = json_encode([]);
            }
        }

        foreach (['openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key'] as $keyName) {
            if ($request->filled($keyName)) {
                $data[$keyName] = trim($request->input($keyName));
            }
        }

        $existingFiles = $assistant->knowledge_files;
        if (!is_array($existingFiles)) {
            $existingFiles = [];
        }
        $hasKnowledgeChanges = false;

        if ($request->hasFile('documents')) {
            $uploadedFiles = $request->file('documents');
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }

            foreach ($uploadedFiles as $file) {
                if ($file && $file->isValid()) {
                    try {
                        $fileName = $file->getClientOriginalName();
                        $path = $file->store('knowledge_base');
                        $fullPath = Storage::path($path);
                        
                        $extractedText = $this->extractTextFromFile($fullPath, $fileName);

                        $existingFiles[] = [
                            'name' => $fileName,
                            'path' => $path,
                            'content' => $extractedText
                        ];
                        $hasKnowledgeChanges = true;
                    } catch (\Throwable $e) {
                        Log::error('Erro no anexo ' . $file->getClientOriginalName() . ': ' . $e->getMessage());
                    }
                }
            }
        }

        if ($hasKnowledgeChanges) {
            $data['knowledge_files'] = array_values($existingFiles);
        }

        $assistant->forceFill($data)->save();

        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas!');
    }

    private function fetchContentFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(25)->get('https://r.jina.ai/' . $url);
            if ($response->successful()) {
                return $this->sanitizeText($response->body());
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao importar URL via Jina Reader ({$url}): " . $e->getMessage());
        }
        return null;
    }

    private function toggleActive(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->is_active = !$assistant->is_active;
        $assistant->save();
        return redirect()->back()->with('success', 'Status alterado!');
    }

    private function destroyOrRemoveFile(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);

        if ($request->has('file_indexes')) {
            $files = $assistant->knowledge_files;
            if (!is_array($files)) $files = [];
            
            $indices = $request->input('file_indexes', []);
            $indices = array_map('intval', $indices);
            rsort($indices);

            foreach ($indices as $index) {
                if (isset($files[$index])) {
                    if (!empty($files[$index]['path'])) {
                        Storage::delete($files[$index]['path']);
                    }
                    array_splice($files, $index, 1);
                }
            }

            $assistant->forceFill(['knowledge_files' => array_values($files)])->save();
            return redirect('/?configure=' . $assistant->id)->with('success', 'Fontes de conhecimento removidas com sucesso.');
        }

        if ($request->has('file_index')) {
            $files = $assistant->knowledge_files;
            if (!is_array($files)) $files = [];
            $index = (int)$request->file_index;

            if (isset($files[$index])) {
                if (!empty($files[$index]['path'])) {
                    Storage::delete($files[$index]['path']);
                }
                array_splice($files, $index, 1);
                $assistant->forceFill(['knowledge_files' => array_values($files)])->save();
            }
            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo/URL removido.');
        }

        $assistant->delete();
        return redirect('/')->with('success', 'Assistente excluído!');
    }

    private function buildSystemPromptWithKnowledge(Assistant $assistant): string
    {
        $prompt = $assistant->system_prompt ?? '';

        $leadFields = is_array($assistant->lead_fields) 
            ? $assistant->lead_fields 
            : json_decode($assistant->lead_fields ?? '[]', true);

        if (!empty($leadFields) && is_array($leadFields)) {
            $prompt .= "\n\n===============================================\n";
            $prompt .= "DIRETRIZES DE TRIAGEM E COLETA DE DADOS:\n";
            $prompt .= "Você deve obter cordialmente do usuário as seguintes informações ao longo do atendimento:\n";
            foreach ($leadFields as $field) {
                $label = $field['label'] ?? '';
                $name = $field['name'] ?? '';
                if ($label) {
                    $prompt .= "• {$label} (Identificador interno: {$name})\n";
                }
            }
            $prompt .= "Solicite estas informações de forma natural e gradual durante a conversa. Não exija tudo de uma vez de forma robótica.\n";
            $prompt .= "===============================================\n";
        }

        $files = $assistant->knowledge_files;

        if (is_array($files) && !empty($files)) {
            $prompt .= "\n\n### BASE DE CONHECIMENTO OFICIAL DA EMPRESA ###\n";
            foreach ($files as $file) {
                $name = $file['name'] ?? 'Arquivo Desconhecido';
                $content = $file['content'] ?? '';

                if (empty($content) && !empty($file['path']) && Storage::exists($file['path'])) {
                    $content = $this->extractTextFromFile(Storage::path($file['path']), $name);
                }

                if (!empty($content)) {
                    $cleanUrl = str_replace('🌐 ', '', $name);
                    $trimmedContent = mb_substr($content, 0, 5000);
                    
                    if (str_starts_with($name, '🌐')) {
                        $prompt .= "\n[TIPO: PAGINA_WEB]\n[URL: {$cleanUrl}]\n[CONTEÚDO]:\n" . $trimmedContent . "\n[FIM DE PAGINA_WEB]\n";
                    } else {
                        $prompt .= "\n[TIPO: DOCUMENTO_ARQUIVO]\n[NOME_ARQUIVO: {$name}]\n[CONTEÚDO]:\n" . $trimmedContent . "\n[FIM DE DOCUMENTO_ARQUIVO]\n";
                    }
                }
            }
        }

        $prompt .= "\n\n===============================================\n";
        $prompt .= "DIRETRIZES ABSOLUTAS E OBRIGATÓRIAS DE RESPOSTA E CITAÇÃO:\n";
        $prompt .= "1. FIDELIDADE AO CONTEÚDO: Responda utilizando EXATAMENTE os termos, frases e explicações das fontes acima. Não invente explicações genéricas.\n";
        $prompt .= "2. REGRA DE CITAÇÃO PARA PAGINA_WEB: Se a sua resposta utilizar informações que vieram de uma fonte marcada como [TIPO: PAGINA_WEB], você É OBRIGADA a incluir na ÚLTIMA LINHA da resposta:\n";
        $prompt .= "URL Consultada: [URL_DA_FONTE]\n";
        $prompt .= "Exemplo: URL Consultada: https://inhouse.com.br/sac-regulado\n";
        $prompt .= "3. REGRA DE PROIBIÇÃO PARA DOCUMENTO_ARQUIVO: Se a sua resposta utilizar informações que vieram de uma fonte marcada como [TIPO: DOCUMENTO_ARQUIVO] (como .docx ou .pdf), é ESTRITAMENTE PROIBIDO adicionar 'URL Consultada' ou citar links de páginas da web.\n";
        $prompt .= "4. FORMATO DE LINKS INTERMEDIÁRIOS: Formate links no meio da frase em Markdown: [Texto](URL_COMPLETA).\n";
        $prompt .= "===============================================\n";

        return $prompt;
    }

    private function extractTextFromFile(string $filePath, string $fileName): string
    {
        if (!file_exists($filePath)) return '';

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $text = '';

        try {
            if (in_array($ext, ['txt', 'md', 'csv', 'json', 'html', 'xml', 'log'])) {
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
                if (class_exists('\Smalot\PdfParser\Parser')) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);
                    $text = $pdf->getText();
                } else {
                    Log::error("A biblioteca smalot/pdfparser não está instalada.");
                    $text = "ERRO INTERNO: Falha na extração. Leitor de PDF não instalado.";
                }
            }
        } catch (\Throwable $e) {
            Log::error("Erro na leitura de {$fileName}: " . $e->getMessage());
            $text = "Erro ao extrair conteúdo deste documento.";
        }

        return $this->sanitizeText($text);
    }

    private function sanitizeText($text): string
    {
        if (!is_string($text) || empty($text)) return '';
        $clean = @mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean);
        return trim(mb_substr($clean, 0, 8000));
    }

    private function chat(Request $request)
    {
        $this->configureTimezone();
        try {
            $assistant = Assistant::find($request->assistant_id);
            if (!$assistant) {
                return response()->json(['reply' => '⚠️ Assistente não encontrado.']);
            }

            $userMessage = (string)$request->input('message');
            $history = $request->input('history', []);
            if (!is_array($history)) $history = [];

            $systemPrompt = $this->buildSystemPromptWithKnowledge($assistant);
            $response = $this->callAiApi($assistant, $systemPrompt, $userMessage, $history);

            return response()->json(['reply' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['reply' => '⚠️ Erro no Chat: ' . $e->getMessage()], 200);
        }
    }

    private function formatTextForWhatsapp(string $text): string
    {
        if (empty($text)) return '';

        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($matches) {
            $label = trim($matches[1]);
            $url = trim($matches[2]);
            return "{$label}:\n👉 {$url}";
        }, $text);

        $text = preg_replace('/^#{1,6}\s*(.+)$/m', '*$1*', $text);
        $text = preg_replace('/^\s*[\*\-]\s+/m', '• ', $text);
        $text = preg_replace('/^\*(\d+\.)\s*\*/m', '$1 *', $text);
        $text = preg_replace('/^\*(\d+\.)\s*/m', '$1 ', $text);
        $text = preg_replace('/\*\*\*(.*?)\*\*\*/s', '*$1*', $text);
        $text = preg_replace('/\*\*(.*?)\*\*/s', '*$1*', $text);
        $text = str_replace('**', '*', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

public function webhook(Request $request, $id)
    {
        $this->configureTimezone();
        $this->ensureWebhookLogTableExists();
        $this->ensureChatMessagesTableExists();

        try {
            $assistant = Assistant::find($id);
            if (!$assistant || !$assistant->is_active) {
                return response()->json(['status' => 'ignored']);
            }

            $rawSender = $request->input('message.sender_pn')
                ?? $request->input('message.chatid')
                ?? $request->input('chat.phone')
                ?? $request->input('chat.wa_chatid')
                ?? $request->input('data.key.remoteJid') 
                ?? $request->input('key.remoteJid') 
                ?? $request->input('phone')
                ?? $request->input('from')
                ?? $request->input('sender') 
                ?? 'desconhecido';

            $sender = is_array($rawSender) ? ($rawSender['user'] ?? json_encode($rawSender)) : (string)$rawSender;
            if (str_contains($sender, '@')) {
                $sender = explode('@', $sender)[0];
            }

            $cleanSender = preg_replace('/[^0-9]/', '', $sender);

            if ($request->input('message.fromMe') === true || $request->input('data.key.fromMe') === true || $request->input('key.fromMe') === true) {
                return response()->json(['status' => 'ignored_from_me']);
            }

            $audioService = new \App\Services\AudioService();

            // Identifica se a mensagem é um áudio e obtém a URL do arquivo
            $audioUrl = $request->input('message.media_url')
                ?? $request->input('message.url')
                ?? $request->input('mediaUrl')
                ?? $request->input('data.message.audioMessage.url')
                ?? $request->input('message.audioMessage.url')
                ?? null;

            $msgType = strtolower($request->input('message.type') ?? $request->input('message.media_type') ?? $request->input('type') ?? '');
            $isAudioMessage = in_array($msgType, ['audio', 'ptt']) || (!empty($audioUrl) && str_contains($audioUrl, '.og'));

            $rawMessage = $request->input('message.content')
                ?? $request->input('message.text')
                ?? $request->input('data.message.conversation')
                ?? $request->input('data.message.extendedTextMessage.text')
                ?? $request->input('message.conversation')
                ?? $request->input('message.extendedTextMessage.text')
                ?? $request->input('text.message')
                ?? $request->input('text')
                ?? $request->input('body')
                ?? '';

            $userMessage = is_array($rawMessage) ? ($rawMessage['text'] ?? $rawMessage['body'] ?? '') : (string)$rawMessage;

            // Se for mensagem de áudio, realiza o download temporário e a transcrição via Whisper
            if ($isAudioMessage && !empty($audioUrl)) {
                try {
                    $tempPath = storage_path('app/temp_audio_' . time() . '_' . rand(1000, 9999) . '.ogg');
                    $audioContent = Http::timeout(30)->get($audioUrl)->body();
                    file_put_contents($tempPath, $audioContent);

                    $transcriptionKey = trim($assistant->openai_api_key ?? '');
                    if ($transcriptionKey) {
                        $transcribedText = $audioService->transcribeAudio($tempPath, $transcriptionKey, 'openai');
                        if (!empty($transcribedText)) {
                            $userMessage = $transcribedText;
                        }
                    }
                    @unlink($tempPath);
                } catch (\Throwable $e) {
                    Log::error("Erro no download/transcrição de áudio: " . $e->getMessage());
                }
            }

            $nowFormatted = now()->setTimezone('America/Sao_Paulo')->toDateTimeString();

            if (empty(trim($userMessage))) {
                DB::table('webhook_logs')->insert([
                    'assistant_id' => $assistant->id,
                    'sender' => substr($sender, 0, 255),
                    'user_message' => '[Mídia / Áudio Sem Texto]',
                    'ai_reply' => 'Ignorado (Não foi possível transcrever)',
                    'wa_send_result' => json_encode(['info' => 'Nenhuma resposta enviada']),
                    'raw_snippet' => json_encode($request->all(), JSON_INVALID_UTF8_IGNORE),
                    'timestamp' => $nowFormatted,
                    'created_at' => $nowFormatted,
                    'updated_at' => $nowFormatted,
                ]);
                return response()->json(['status' => 'no_message']);
            }

            $contextLimit = (int) ($assistant->context_limit ?? 12);

            $historyRecords = DB::table('chat_messages')
                ->where('assistant_id', $assistant->id)
                ->where('phone_number', $cleanSender)
                ->orderBy('id', 'desc')
                ->limit($contextLimit)
                ->get()
                ->reverse();

            $history = [];
            foreach ($historyRecords as $msg) {
                $history[] = [
                    'role' => $msg->role,
                    'content' => $msg->content
                ];
            }

            $systemPrompt = $this->buildSystemPromptWithKnowledge($assistant);
            $aiReply = $this->callAiApi($assistant, $systemPrompt, $userMessage, $history);

            // Grava histórico da conversa
            DB::table('chat_messages')->insert([
                [
                    'assistant_id' => $assistant->id,
                    'phone_number' => $cleanSender,
                    'role' => 'user',
                    'content' => $isAudioMessage ? '[🎙️ Áudio]: ' . $userMessage : $userMessage,
                    'created_at' => $nowFormatted,
                    'updated_at' => $nowFormatted,
                ],
                [
                    'assistant_id' => $assistant->id,
                    'phone_number' => $cleanSender,
                    'role' => 'assistant',
                    'content' => $aiReply,
                    'created_at' => $nowFormatted,
                    'updated_at' => $nowFormatted,
                ]
            ]);

            // Se o usuário mandou áudio, gera resposta em voz e separa links
            if ($isAudioMessage) {
                $separated = $audioService->separateLinksFromText($aiReply);
                $googleKey = env('GOOGLE_API_KEY_TTS');

                $audioPublicUrl = $audioService->textToSpeech($separated['audio_text'], $googleKey);

                if ($audioPublicUrl) {
                    $waResult = $this->sendWhatsappAudioMessage($assistant, $sender, $audioPublicUrl);

                    // Se existirem links na resposta, envia uma mensagem de texto adicional com os links
                    if (!empty($separated['extracted_links'])) {
                        $this->sendWhatsappMessage($assistant, $sender, $separated['extracted_links']);
                    }
                } else {
                    // Fallback caso a API do Google falhe: responde em texto normal
                    $formattedReply = $this->formatTextForWhatsapp($aiReply);
                    $waResult = $this->sendWhatsappMessage($assistant, $sender, $formattedReply);
                }
            } else {
                // Entrada em texto normal
                $formattedReply = $this->formatTextForWhatsapp($aiReply);
                $waResult = $this->sendWhatsappMessage($assistant, $sender, $formattedReply);
            }

            DB::table('webhook_logs')->insert([
                'assistant_id' => $assistant->id,
                'sender' => substr($sender, 0, 255),
                'user_message' => $isAudioMessage ? '[🎙️ Áudio]: ' . $userMessage : $userMessage,
                'ai_reply' => $aiReply,
                'wa_send_result' => json_encode($waResult, JSON_INVALID_UTF8_IGNORE),
                'raw_snippet' => json_encode($request->all(), JSON_INVALID_UTF8_IGNORE),
                'timestamp' => $nowFormatted,
                'created_at' => $nowFormatted,
                'updated_at' => $nowFormatted,
            ]);

            return response()->json(['status' => 'success', 'reply' => $aiReply]);
        } catch (\Throwable $e) {
            $nowFormatted = now()->setTimezone('America/Sao_Paulo')->toDateTimeString();
            DB::table('webhook_logs')->insert([
                'assistant_id' => $id,
                'sender' => 'Erro Interno',
                'user_message' => 'Falha Crítica',
                'ai_reply' => 'Erro: ' . $e->getMessage(),
                'wa_send_result' => json_encode(['error' => $e->getMessage()]),
                'raw_snippet' => json_encode($request->all(), JSON_INVALID_UTF8_IGNORE),
                'timestamp' => $nowFormatted,
                'created_at' => $nowFormatted,
                'updated_at' => $nowFormatted,
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }

    private function callAiApi(Assistant $assistant, string $systemPrompt, string $userMessage, array $history = []): string
    {
        $provider = $assistant->provider ?? 'openai';

        if ($provider === 'openai') {
            $key = trim($assistant->openai_api_key ?? '');
            if (!$key) return 'Erro: Chave API da OpenAI não configurada.';

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                }
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $res = Http::withToken($key)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $assistant->model ?? 'gpt-4o-mini',
                'messages' => $messages,
            ]);

            if ($res->failed()) return 'Erro na API OpenAI: ' . json_encode($res->json());
            return $res->json('choices.0.message.content') ?? 'Resposta vazia da OpenAI.';
        }

        if ($provider === 'gemini') {
            $key = trim($assistant->gemini_api_key ?? '');
            if (!$key) return 'Erro: Chave API do Gemini não configurada.';

            $res = Http::post("https://generativelanguage.googleapis.com/v1beta/models/{$assistant->model}:generateContent?key={$key}", [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['parts' => [['text' => $userMessage]]]]
            ]);

            if ($res->failed()) return 'Erro na API Gemini: ' . json_encode($res->json());
            return $res->json('candidates.0.content.parts.0.text') ?? 'Resposta vazia do Gemini.';
        }

        if ($provider === 'anthropic') {
            $key = trim($assistant->anthropic_api_key ?? '');
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

            if ($res->failed()) return 'Erro na API Anthropic: ' . json_encode($res->json());
            return $res->json('content.0.text') ?? 'Resposta vazia da Anthropic.';
        }

        if ($provider === 'grok') {
            $key = trim($assistant->grok_api_key ?? '');
            if (!$key) return 'Erro: Chave API do Grok não configurada.';

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                }
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $res = Http::withToken($key)->post('https://api.x.ai/v1/chat/completions', [
                'model' => $assistant->model ?? 'grok-2-mini',
                'messages' => $messages
            ]);

            if ($res->failed()) return 'Erro na API Grok: ' . json_encode($res->json());
            return $res->json('choices.0.message.content') ?? 'Resposta vazia do Grok.';
        }

        return 'Provedor de IA não configurado.';
    }

    private function testAi(Request $request)
    {
        $provider = $request->provider;
        $apiKey = trim($request->api_key ?? '');

        if (!$apiKey) return response()->json(['success' => false, 'message' => 'Informe uma chave API válida.']);

        try {
            if ($provider === 'openai') {
                $res = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'Responda OK']]
                ]);
                return response()->json([
                    'success' => $res->successful(), 
                    'message' => $res->successful() ? 'Conexão OpenAI OK!' : ($res->json('error.message') ?? 'Chave de API rejeitada.')
                ]);
            }
            if ($provider === 'gemini') {
                $res = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => 'Responda OK']]]]
                ]);
                return response()->json([
                    'success' => $res->successful(), 
                    'message' => $res->successful() ? 'Conexão Gemini OK!' : 'Falha ao autenticar chave Gemini.'
                ]);
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
            $cleanTo = preg_replace('/[^0-9]/', '', $to);
            $baseUrl = rtrim($assistant->whatsapp_url, '/');
            $token = trim($assistant->whatsapp_token);

            if (str_contains($baseUrl, 'uazapi.com') || $assistant->whatsapp_provider === 'uazapi') {
                $endpoint = $baseUrl . '/send/text';
                
                $payload = [
                    'token' => $token,
                    'number' => $cleanTo,
                    'text' => $message
                ];

                $response = Http::withHeaders([
                    'token' => $token,
                    'Client-Token' => $token,
                    'client-token' => $token,
                    'apikey' => $token,
                    'Content-Type' => 'application/json'
                ])->post($endpoint . '?token=' . urlencode($token), $payload);

            } else {
                $endpoint = $baseUrl . '/message/sendText/' . $assistant->whatsapp_instance;
                $payload = [
                    'number' => $cleanTo,
                    'phone' => $cleanTo,
                    'text' => $message
                ];

                $response = Http::withHeaders([
                    'token' => $token,
                    'apikey' => $token,
                    'Content-Type' => 'application/json'
                ])->post($endpoint, $payload);
            }

            return ['success' => $response->successful(), 'error' => $response->failed() ? $response->body() : null];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendWhatsappAudioMessage(Assistant $assistant, string $to, string $audioUrl): array
    {
        if (empty($assistant->whatsapp_url) || empty($assistant->whatsapp_token)) {
            return ['success' => false, 'error' => 'WhatsApp não configurado.'];
        }

        try {
            $cleanTo = preg_replace('/[^0-9]/', '', $to);
            $baseUrl = rtrim($assistant->whatsapp_url, '/');
            $token = trim($assistant->whatsapp_token);

            if (str_contains($baseUrl, 'uazapi.com') || $assistant->whatsapp_provider === 'uazapi') {
                $endpoint = $baseUrl . '/send/media';
                
                $payload = [
                    'token' => $token,
                    'number' => $cleanTo,
                    'media' => $audioUrl,
                    'type' => 'audio',
                    'mimetype' => 'audio/mp3'
                ];

                $response = Http::withHeaders([
                    'token' => $token,
                    'Client-Token' => $token,
                    'client-token' => $token,
                    'apikey' => $token,
                    'Content-Type' => 'application/json'
                ])->post($endpoint . '?token=' . urlencode($token), $payload);

            } else {
                $endpoint = $baseUrl . '/message/sendWhatsAppAudio/' . $assistant->whatsapp_instance;
                $payload = [
                    'number' => $cleanTo,
                    'audio' => $audioUrl
                ];

                $response = Http::withHeaders([
                    'token' => $token,
                    'apikey' => $token,
                    'Content-Type' => 'application/json'
                ])->post($endpoint, $payload);
            }

            return ['success' => $response->successful(), 'error' => $response->failed() ? $response->body() : null];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}