<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    private function configureTimezone()
    {
        date_default_timezone_set('America/Sao_Paulo');
    }

    public function handle(Request $request)
    {
        $this->configureTimezone();

        if ($request->isMethod('post')) {
            $action = $request->input('action');
            if ($action === 'store_agent') return $this->storeAgent($request);
            if ($action === 'update_agent') return $this->updateAgent($request);
            if ($action === 'delete_agent') return $this->deleteAgent($request);

            if ($action === 'store_department') return $this->storeDepartment($request);
            if ($action === 'update_department') return $this->updateDepartment($request);
            if ($action === 'delete_department') return $this->deleteDepartment($request);
        }

        // Busca os assistentes para popular o filtro no topo
        $assistants = DB::table('assistants')->orderBy('name', 'asc')->get();
        
        // Pega o ID do assistente na URL, se não tiver, pega o primeiro da lista
        $selectedAssistantId = (int) $request->query('assistant_id');
        if (!$selectedAssistantId && $assistants->isNotEmpty()) {
            $selectedAssistantId = $assistants->first()->id;
        }

        // Busca apenas os departamentos DO ASSISTENTE SELECIONADO
        $departments = DB::table('departments')
            ->where('assistant_id', $selectedAssistantId)
            ->orderBy('name', 'asc')
            ->get();
        
        // Busca apenas os agentes que pertencem aos departamentos carregados acima
        $deptIds = $departments->pluck('id');
        $agents = DB::table('human_agents')
            ->whereIn('department_id', $deptIds)
            ->orderBy('name', 'asc')
            ->get();
        
        $currentView = 'equipe';

        return view('agents.index', compact('departments', 'agents', 'assistants', 'currentView', 'selectedAssistantId'));
    }

    private function storeDepartment(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $request->validate(['name' => 'required|string|max:255']);

        DB::table('departments')->insert([
            'assistant_id' => $assistantId,
            'name' => trim($request->name),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('success', 'Departamento criado com sucesso!');
    }

    private function updateDepartment(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $request->validate([
            'department_id' => 'required|integer',
            'name' => 'required|string|max:255'
        ]);
        
        DB::table('departments')->where('id', $request->department_id)->update([
            'name' => trim($request->name),
            'updated_at' => now(),
        ]);
        
        return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('success', 'Departamento atualizado!');
    }

    private function deleteDepartment(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $deptId = (int)$request->input('department_id');
        
        DB::table('human_agents')->where('department_id', $deptId)->delete();
        DB::table('departments')->where('id', $deptId)->delete();
        
        return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('success', 'Departamento excluído!');
    }

    private function storeAgent(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $departmentId = (int) $request->input('department_id');
        $email = strtolower(trim((string)$request->input('email')));
        $name = trim((string)$request->input('name'));

        if (!$departmentId || !$email || !$name) {
            return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('error', 'Preencha todos os campos do agente.');
        }

        $duplicate = DB::table('human_agents')
            ->where('department_id', $departmentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('error', "Bloqueado: O e-mail '{$email}' já está em uso por '{$duplicate->name}' neste departamento.");
        }

        DB::table('human_agents')->insert([
            'department_id' => $departmentId,
            'name' => $name,
            'email' => $email,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('success', "Agente '{$name}' adicionado com sucesso!");
    }

    private function updateAgent(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $agentId = (int)$request->input('agent_id');
        $departmentId = (int)$request->input('department_id');
        $name = trim((string)$request->input('name'));
        $email = strtolower(trim((string)$request->input('email')));

        $duplicate = DB::table('human_agents')
            ->where('department_id', $departmentId)
            ->where('id', '!=', $agentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('error', "Bloqueado: O e-mail '{$email}' já pertence a outro agente neste departamento.");
        }

        DB::table('human_agents')->where('id', $agentId)->update([
            'name' => $name,
            'email' => $email,
            'updated_at' => now(),
        ]);

        return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('success', "Agente '{$name}' atualizado!");
    }

    private function deleteAgent(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $agentId = (int)$request->input('agent_id');
        
        DB::table('human_agents')->where('id', $agentId)->delete();
        
        return redirect('/?view=equipe&assistant_id=' . $assistantId)->with('success', 'Agente excluído com sucesso!');
    }
}