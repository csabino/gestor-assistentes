<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\HumanAgent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        // Busca todos os departamentos e já traz os agentes junto (ordem alfabética)
        $departments = Department::with(['agents' => function($query) {
            $query->orderBy('name', 'asc');
        }])->orderBy('name', 'asc')->get();

        return view('agents.index', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        Department::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect('/equipe')->with('success', 'Departamento criado com sucesso!');
    }

    public function destroyDepartment(Request $request)
    {
        Department::findOrFail($request->department_id)->delete();
        return redirect('/equipe')->with('success', 'Departamento e seus agentes foram removidos!');
    }

    public function storeAgent(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255'
        ]);

        HumanAgent::create($request->only('department_id', 'name', 'email', 'phone'));

        return redirect('/equipe')->with('success', 'Agente adicionado com sucesso!');
    }

    public function destroyAgent(Request $request)
    {
        HumanAgent::findOrFail($request->agent_id)->delete();
        return redirect('/equipe')->with('success', 'Agente removido da equipe!');
    }
}