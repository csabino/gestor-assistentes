<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 'grok_api_key'
        ]));

        // Lógica para upload de MÚLTIPLOS arquivos de uma vez
        if ($request->hasFile('documents')) {
            $files = $assistant->knowledge_files ?? [];
            
            foreach ($request->file('documents') as $file) {
                $path = $file->store('knowledge_bases');
                $files[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => $file->getClientMimeType()
                ];
            }
            
            $assistant->update(['knowledge_files' => $files]);
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

        // Se a requisição veio para excluir APENAS um arquivo específico
        if ($request->has('file_index')) {
            $files = $assistant->knowledge_files ?? [];
            $index = $request->file_index;

            if (isset($files[$index])) {
                // Deleta o arquivo do disco do servidor
                Storage::delete($files[$index]['path']);
                // Remove do array e reindexa
                unset($files[$index]);
                $assistant->update(['knowledge_files' => array_values($files)]);
            }

            return redirect('/?configure=' . $assistant->id)->with('success', 'Arquivo removido da base de conhecimento!');
        }

        // Caso contrário, exclui o assistente completo
        $assistant->delete();
        return redirect('/')->with('success', 'Assistente removido!');
    }
}