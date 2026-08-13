<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    // Mostra a tela principal com a lista de assistentes
    public function index()
    {
        $assistants = Assistant::all();
        return view('assistants.index', compact('assistants'));
    }

    // Salva um novo assistente no banco de dados
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Assistant::create([
            'name' => $request->name,
            'status' => 'active'
        ]);

        return back()->with('success', 'Assistente criado com sucesso!');
    }
}