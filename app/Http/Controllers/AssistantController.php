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
        $action = $request->input('action');
        if ($action === 'test_ai') return $this->testAiConnection($request);
        if ($action === 'test_whatsapp') return $this->testWaConnection($request);
        if ($action === 'status_whatsapp') return $this->statusWaConnection($request);
        if ($action === 'disconnect_whatsapp') return $this->disconnectWaConnection($request);

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

        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas!');
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

        if (empty($apiKey)) return response()->json(['success' => false, 'message' => 'Informe a API Key para testar.']);

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

            return $res->successful() 
                ? response()->json(['success' => true, 'message' => 'Conexão estabelecida com sucesso!']) 
                : response()->json(['success' => false, 'message' => 'Falha na conexão de IA.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao conectar na IA.']);
        }
    }

    // NOVA FUNÇÃO: Checagem rápida e silenciosa de status
    private function statusWaConnection(Request $request)
    {
        $provider = $request->input('provider');
        $url = rtrim($request->input('url'), '/');
        $instance = trim($request->input('instance'));
        $token = trim($request->input('token'));

        if (empty($provider) || empty($url) || empty($token)) {
            return response()->json(['connected' => false]);
        }

        try {
            $headers = ['token' => $token, 'apikey' => $token, 'Client-Token' => $token, 'Authorization' => "Bearer {$token}"];

            if ($provider === 'uazapi') {
                $res = Http::withHeaders($headers)->timeout(5)->get("{$url}/instance/connect");
            } elseif ($provider === 'evolution') {
                $res = Http::withHeaders($headers)->timeout(5)->get("{$url}/instance/connect/{$instance}");
            } else {
                return response()->json(['connected' => false]);
            }

            if ($res->successful()) {
                $data = $res->json();
                $inst = $data['instance'] ?? $data;
                $status = strtolower($inst['state'] ?? $inst['status'] ?? $data['status'] ?? $data['state'] ?? '');
                
                if ($status === 'open' || $status === 'connected' || ($data['connected'] ?? false) === true) {
                    return response()->json(['connected' => true]);
                }
            }
            return response()->json(['connected' => false]);
        } catch (\Exception $e) {
            return response()->json(['connected' => false]);
        }
    }

    // NOVA FUNÇÃO: Desconectar / Fazer Logout da Instância remotamente
    private function disconnectWaConnection(Request $request)
    {
        $provider = $request->input('provider');
        $url = rtrim($request->input('url'), '/');
        $instance = trim($request->input('instance'));
        $token = trim($request->input('token'));

        try {
            $headers = ['token' => $token, 'apikey' => $token, 'Client-Token' => $token, 'Authorization' => "Bearer {$token}"];

            if ($provider === 'uazapi') {
                Http::withHeaders($headers)->timeout(8)->delete("{$url}/instance/logout");
            } elseif ($provider === 'evolution') {
                Http::withHeaders($headers)->timeout(8)->delete("{$url}/instance/logout/{$instance}");
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    // Geração do QR Code e Conexão Manual
    private function testWaConnection(Request $request)
    {
        $provider = $request->input('provider');
        $url = rtrim($request->input('url'), '/');
        $instance = trim($request->input('instance'));
        $token = trim($request->input('token'));

        try {
            $headers = [
                'token' => $token, 'apikey' => $token, 'Client-Token' => $token, 
                'Authorization' => "Bearer {$token}", 'Content-Type' => 'application/json'
            ];

            if ($provider === 'uazapi') {
                $response = Http::withHeaders($headers)->timeout(10)->post("{$url}/instance/connect", (object)[]);

                if ($response->successful()) {
                    $data = $response->json();
                    $inst = $data['instance'] ?? $data;
                    $status = strtolower($inst['status'] ?? $inst['state'] ?? $data['status'] ?? $data['state'] ?? '');

                    if ($status === 'open' || $status === 'connected' || ($data['connected'] ?? false) === true) {
                        return response()->json(['success' => true, 'connected' => true, 'message' => '✅ WhatsApp pareado com sucesso!']);
                    }

                    $qr = $this->extractQrCode($data);
                    if ($qr) return response()->json(['success' => true, 'connected' => false, 'qr' => $qr, 'message' => 'Escaneie o QR Code abaixo:']);

                    return response()->json(['success' => true, 'connected' => false, 'qr' => null, 'message' => "Aguardando imagem (Status: {$status})..."]);
                }
                return response()->json(['success' => false, 'message' => "Erro na UaZapi. Verifique credenciais."]);
            }

            if ($provider === 'evolution') {
                $response = Http::withHeaders($headers)->timeout(10)->get("{$url}/instance/connect/{$instance}");
                if ($response->successful()) {
                    $data = $response->json();
                    $status = strtolower($data['instance']['state'] ?? $data['state'] ?? '');
                    
                    if ($status === 'open' || $status === 'connected') return response()->json(['success' => true, 'connected' => true, 'message' => '✅ WhatsApp pareado!']);
                    
                    $qr = $this->extractQrCode($data);
                    if ($qr) return response()->json(['success' => true, 'connected' => false, 'qr' => $qr, 'message' => 'Escaneie a imagem abaixo:']);
                    
                    return response()->json(['success' => true, 'connected' => false, 'qr' => null, 'message' => "Aguardando imagem..."]);
                }
                return response()->json(['success' => false, 'message' => "Erro na Evolution API."]);
            }

            return response()->json(['success' => true, 'message' => "Integração em desenvolvimento."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro de comunicação de rede.']);
        }
    }

    private function extractQrCode($data)
    {
        if (!is_array($data)) return null;
        $candidates = [$data['instance']['qrcode'] ?? null, $data['instance']['qr'] ?? null, $data['qrcode'] ?? null, $data['base64'] ?? null, $data['qr'] ?? null];

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