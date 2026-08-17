<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\HumanAgent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function handle(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'store_department') {
            return $this->storeDepartment($request);
        }
        if ($action === 'delete_department') {
            return $this->destroyDepartment($request);
        }
        if ($action === 'store_agent') {
            return $this->storeAgent($request);
        }
        if ($action === 'delete_agent') {
            return $this->destroyAgent($request);
        }

        return $this->index();
    }

    public function index()
    {
        $departments = Department::with(['agents' => function($query) {
            $query->orderBy('name', 'asc');
        }])->orderBy('name', 'asc')->get();

        return view('agents.index', compact('departments'));
    }

    private function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        Department::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect('/?view=equipe')->with('success', 'Departamento criado com sucesso!');
    }

    private function destroyDepartment(Request $request)
    {
        Department::findOrFail($request->department_id)->delete();
        return redirect('/?view=equipe')->with('success', 'Departamento e seus agentes foram removidos!');
    }

    private function storeAgent(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255'
        ]);

        HumanAgent::create($request->only('department_id', 'name', 'email', 'phone'));

        return redirect('/?view=equipe')->with('success', 'Agente adicionado com sucesso!');
    }

    private function destroyAgent(Request $request)
    {
        HumanAgent::findOrFail($request->agent_id)->delete();
        return redirect('/?view=equipe')->with('success', 'Agente removido da equipe!');
    }
}