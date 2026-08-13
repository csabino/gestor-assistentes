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
        $instance = $request->input('instance');
        $token = $request->input('token');

        if (empty($provider) || empty($instance) || empty($token)) {
            return response()->json(['success' => false, 'message' => 'Preencha URL, Instância e Token para testar a conexão.']);
        }

        try {
            // LÓGICA ESPECÍFICA PARA A UAZAPI
            if ($provider === 'uazapi') {
                $response = Http::withHeaders([
                    'token' => $token,
                    'apikey' => $token,
                    'Client-Token' => $token
                ])->get("{$url}/instance/connect");

                // Se a UaZapi retornar 404 na rota direta, tenta o fallback com o nome da instância no path
                if ($response->status() === 404) {
                    $response = Http::withHeaders(['token' => $token, 'apikey' => $token])->get("{$url}/instance/connect/{$instance}");
                }

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Extrai o QR Code
                    $qr = $data['base64'] ?? $data['qrcode'] ?? $data['instance']['qrcode'] ?? null;
                    if ($qr) {
                        if (!str_starts_with($qr, 'data:image')) {
                            $qr = 'data:image/png;base64,' . $qr;
                        }
                        return response()->json([
                            'success' => true,
                            'message' => 'Escaneie o QR Code abaixo com seu WhatsApp:',
                            'qr' => $qr
                        ]);
                    }

                    // Se já estiver pareado
                    $state = $data['instance']['state'] ?? $data['status'] ?? $data['state'] ?? null;
                    if ($state === 'open' || $state === 'connected' || ($data['connected'] ?? false) === true) {
                        return response()->json(['success' => true, 'message' => '✅ WhatsApp pareado e conectado com sucesso!']);
                    }

                    return response()->json(['success' => true, 'message' => 'Status da instância: ' . ($state ?? 'Aguardando QR Code')]);
                } else {
                    $errorData = $response->json();
                    $errorMsg = $errorData['message'] ?? $errorData['error'] ?? 'Verifique se a URL e o Token da UaZapi estão corretos.';
                    return response()->json(['success' => false, 'message' => "Erro {$response->status()}: {$errorMsg}"]);
                }
            }

            // LÓGICA PARA EVOLUTION API
            if ($provider === 'evolution') {
                $response = Http::withHeaders([
                    'apikey' => $token,
                    'Authorization' => "Bearer {$token}"
                ])->get("{$url}/instance/connect/{$instance}");

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['base64'])) {
                        return response()->json(['success' => true, 'message' => 'Escaneie o QR Code abaixo:', 'qr' => $data['base64']]);
                    } elseif (isset($data['instance']['state']) && $data['instance']['state'] === 'open') {
                        return response()->json(['success' => true, 'message' => '✅ WhatsApp conectado com sucesso!']);
                    } else {
                        return response()->json(['success' => true, 'message' => 'Status: ' . ($data['instance']['state'] ?? 'Desconhecido')]);
                    }
                } else {
                    return response()->json(['success' => false, 'message' => "Erro {$response->status()} na Evolution API."]);
                }
            }

            return response()->json(['success' => true, 'message' => "Teste dinâmico para {$provider} em desenvolvimento."]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro de rede ao conectar no servidor da UaZapi.']);
        }
    }
}