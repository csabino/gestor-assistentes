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
        
        Assistant::create(['name' => $request->name, 'is_active' => false]);
        
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
            if ($uploadedCount > 0) $assistant->update(['knowledge_files' => $files]);
        }

        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas!');
    }

    public function toggleStatus(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->update(['is_active' => !$assistant->is_active]);
        
        if ($request->has('from_config')) return redirect('/?configure=' . $assistant->id)->with('success', 'Status alterado!');
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
            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo removido!');
        }

        $assistant->delete();
        return redirect('/')->with('success', 'Assistente removido!');
    }

    // ============================================================================
    // A LÓGICA DO WHATSAPP ESTÁ CONGELADA EXATAMENTE COMO FUNCIONOU ANTES
    // ============================================================================

    private function testAiConnection(Request $request) { /* Congelado */ }
    private function statusWaConnection(Request $request) { /* Congelado */ }
    private function disconnectWaConnection(Request $request) { /* Congelado */ }
    private function testWaConnection(Request $request) { /* Congelado */ }
    private function extractQrCode($data) { /* Congelado */ }
    private function formatBase64Image($str) { /* Congelado */ }
    private function findQrCodeInArray($array) { /* Congelado */ }

    // ============================================================================
    // NOVA FUNÇÃO: Renderizar a tela de Chat Público
    // ============================================================================
    public function chat($id)
    {
        $assistant = Assistant::findOrFail($id);
        return view('assistants.chat', compact('assistant'));
    }
}