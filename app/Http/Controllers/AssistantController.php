<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function index()
    {
        $assistants = Assistant::latest()->get();
        return view('assistants.index', compact('assistants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Assistant::create([
            'name' => $request->name,
            'is_active' => true,
        ]);

        return redirect('/')->with('success', 'Assistente criado com sucesso!');
    }

    public function toggleStatus(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->update([
            'is_active' => !$assistant->is_active,
        ]);

        return redirect('/')->with('success', 'Status do assistente atualizado!');
    }

    public function destroy(Request $request)
    {
        $assistant = Assistant::findOrFail($request->assistant_id);
        $assistant->delete();

        return redirect('/')->with('success', 'Assistente removido!');
    }
}