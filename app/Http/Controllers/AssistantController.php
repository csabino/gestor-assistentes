<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        $assistants = Assistant::orderBy('name', 'asc')->get();
        $configuring = $request->has('configure') ? Assistant::find($request->configure) : null;
        
        return view('assistants.index', compact('assistants', 'configuring'));
    }

    public function store(Request $request)
    {
        if ($request->input('action') === 'test_ai') {
            return $this->testAiConnection($request);
        }
        if ($request->input('action') === 'test_whatsapp') {
            return $this->testWaConnection($request);
        }

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
                return redirect('/?configure=' . $assistant->id)->with('error', 'Falha ao subir o arquivo. Verifique o limite de tamanho.');
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

    private function testAiConnection(Request $request)
    {
        $provider = $request->input('provider');
        $apiKey = $request->input('api_key');

        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'Informe a API Key do provedor selecionado para testar.']);
        }

        try {
            if ($provider === 'openai') {
                $res = Http::withToken($apiKey)->get('https://api.openai.com/v1/models');
            } elseif ($provider === 'gemini') {
                $res = Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            } elseif ($provider === 'anthropic') {
                $res = Http::withHeaders(['x-api-key' => $apiKey, 'anthropic-version' => '2023-06-01'])->get('https://api.anthropic.com/v1/models');
            } elseif ($provider === 'grok') {
                $res = Http::withToken($apiKey)->get('https://api.x.ai/v1/models');
            } else {
                return response()->json(['success' => false, 'message' => 'Provedor inválido.']);
            }

            if ($res->successful()) {
                return response()->json(['success' => true, 'message' => 'Conexão estabelecida com sucesso!']);
            } else {
                $err = $res->json()['error']['message'] ?? 'Erro de autenticação.';
                return response()->json(['success' => false, 'message' => 'Falha na conexão: ' . substr($err, 0, 120)]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao conectar: ' . $e->getMessage()]);
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
            // LÓGICA UNIFICADA: UAZAPI E EVOLUTION API
            if ($provider === 'uazapi' || $provider === 'evolution') {
                
                // Agora enviamos a ROTA CORRETA com o /{$instance} no final
                $response = Http::withHeaders([
                    'apikey' => $token,
                    'Authorization' => "Bearer {$token}"
                ])->timeout(10)->get("{$url}/instance/connect/{$instance}");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // A API pode retornar os dados na raiz ou dentro de 'instance'
                    $inst = $data['instance'] ?? $data;
                    $status = strtolower($inst['state'] ?? $inst['status'] ?? $data['status'] ?? $data['state'] ?? '');
                    
                    // Verifica se já está pareado e logado
                    if ($status === 'open' || $status === 'connected' || ($data['connected'] ?? false) === true) {
                        return response()->json([
                            'success' => true,
                            'connected' => true,
                            'message' => '✅ WhatsApp pareado e conectado com sucesso!'
                        ]);
                    }

                    // Extrai a imagem do QR Code
                    $qr = $inst['qrcode'] ?? $inst['qr'] ?? $data['qrcode'] ?? $data['base64'] ?? null;

                    // Valida se o QR Code já existe e não é uma string vazia ""
                    if (!empty($qr) && is_string($qr) && strlen(trim($qr)) > 30) {
                        return response()->json([
                            'success' => true,
                            'connected' => false,
                            'qr' => $this->formatBase64Image($qr),
                            'message' => 'Abra seu WhatsApp > Aparelhos Conectados e escaneie a imagem abaixo:'
                        ]);
                    }

                    // Se a API retornou 200 OK mas o QR Code veio vazio, avisa o polling do Javascript
                    return response()->json([
                        'success' => true,
                        'connected' => false,
                        'qr' => null,
                        'message' => "Instância ligada (Status: " . ($status ?: 'desconectado') . "). O servidor está gerando a imagem do QR Code..."
                    ]);
                } else {
                    $errorData = $response->json();
                    $errorMsg = $errorData['message'] ?? $errorData['error'] ?? 'Verifique se a URL, Instância e Token estão corretos.';
                    return response()->json([
                        'success' => false,
                        'message' => "Erro {$response->status()}: {$errorMsg}"
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => "Integração para {$provider} em desenvolvimento."]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro de comunicação de rede com o servidor.']);
        }
    }

    private function formatBase64Image($str) {
        $str = trim($str);
        if (!str_starts_with($str, 'data:image')) {
            if (str_contains($str, 'base64,')) {
                $str = 'data:image/png;' . substr($str, strpos($str, 'base64,'));
            } else {
                $str = 'data:image/png;base64,' . $str;
            }
        }
        return $str;
    }
}