<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssistantController extends Controller
{
    public function index(Request $request)
    {
        $assistants = Assistant::latest()->get();
        $configuring = $request->has('configure') ? Assistant::find($request->configure) : null;
        
        return view('assistants.index', compact('assistants', 'configuring'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Assistant::create(['name' => $request->name, 'is_active' => true]);
        return redirect('/')->with('success', 'Assistente criado com sucesso!');
    }

    public function updateConfig(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        
        // Atualiza os campos de texto
        $assistant->update($request->only([
            'provider', 'model', 'system_prompt', 
            'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key'
        ]));

        // Lógica de Upload da Base de Conhecimento
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('knowledge_bases'); // Salva na pasta segura do servidor
            
            $files = $assistant->knowledge_files ?? [];
            $files[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'type' => $file->getClientMimeType()
            ];
            
            $assistant->update(['knowledge_files' => $files]);
        }

        return redirect('/?configure=' . $assistant->id)->with('success', 'Configurações atualizadas e salvas!');
    }

    public function toggleStatus(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->update(['is_active' => !$assistant->is_active]);
        
        // Se estiver na tela de configuração, volta para ela
        if ($request->has('from_config')) {
            return redirect('/?configure=' . $assistant->id)->with('success', 'Status alterado!');
        }
        return redirect('/')->with('success', 'Status atualizado!');
    }

    public function destroy(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->delete();
        return redirect('/')->with('success', 'Assistente removido!');
    }
}