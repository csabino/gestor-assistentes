<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\Department;
use App\Models\HumanAgent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function handle(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'store_department') return $this->storeDepartment($request);
        if ($action === 'update_department') return $this->updateDepartment($request);
        if ($action === 'delete_department') return $this->destroyDepartment($request);
        
        if ($action === 'store_agent') return $this->storeAgent($request);
        if ($action === 'update_agent') return $this->updateAgent($request);
        if ($action === 'delete_agent') return $this->destroyAgent($request);

        return $this->index($request);
    }

    public function index(Request $request)
    {
        $assistants = Assistant::orderBy('name', 'asc')->get();

        if ($assistants->count() === 0) {
            return redirect('/')->with('error', 'Crie pelo menos um assistente/robô antes de configurar as equipes!');
        }

        $currentAssistantId = $request->input('assistant_id', session('last_equipe_ast_id', $assistants->first()->id));

        if (!$assistants->contains('id', $currentAssistantId)) {
            $currentAssistantId = $assistants->first()->id;
        }

        session(['last_equipe_ast_id' => $currentAssistantId]);

        $departments = Department::with(['agents' => function($query) {
            $query->orderBy('name', 'asc');
        }])
        ->where('assistant_id', $currentAssistantId)
        ->orderBy('name', 'asc')
        ->get();

        return view('agents.index', compact('departments', 'assistants', 'currentAssistantId'));
    }

    private function storeDepartment(Request $request)
    {
        $request->validate([
            'assistant_id' => 'required|exists:assistants,id',
            'name' => 'required|string|max:255'
        ]);
        
        Department::create([
            'assistant_id' => $request->assistant_id,
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()->back()->with('success', 'Departamento criado com sucesso!');
    }

    private function updateDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255'
        ]);

        $dept = Department::findOrFail($request->department_id);
        $dept->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()->back()->with('success', 'Departamento atualizado com sucesso!');
    }

    private function destroyDepartment(Request $request)
    {
        Department::findOrFail($request->department_id)->delete();
        return redirect()->back()->with('success', 'Departamento e seus agentes foram removidos!');
    }

    private function storeAgent(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50'
        ]);

        HumanAgent::create($request->only('department_id', 'name', 'email', 'phone'));

        return redirect()->back()->with('success', 'Agente adicionado com sucesso!');
    }

    private function updateAgent(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:human_agents,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50'
        ]);

        $agent = HumanAgent::findOrFail($request->agent_id);
        $agent->update($request->only('name', 'email', 'phone'));

        return redirect()->back()->with('success', 'Dados do agente atualizados com sucesso!');
    }

    private function destroyAgent(Request $request)
    {
        HumanAgent::findOrFail($request->agent_id)->delete();
        return redirect()->back()->with('success', 'Agente removido da equipe!');
    }
}