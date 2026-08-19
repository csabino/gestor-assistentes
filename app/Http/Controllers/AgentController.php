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

        // Filtro de status (Padrão: ativo)
        $statusFilter = $request->query('status', 'ativo');

        // Busca os assistentes baseados no filtro de status
        $assistantsQuery = DB::table('assistants')->orderBy('name', 'asc');
        if ($statusFilter === 'ativo') {
            $assistantsQuery->where('is_active', 1);
        } elseif ($statusFilter === 'inativo') {
            $assistantsQuery->where('is_active', 0);
        }
        $assistants = $assistantsQuery->get();
        
        // Pega o ID do assistente na URL, se não tiver ou não existir na lista atual, pega o primeiro
        $selectedAssistantId = (int) $request->query('assistant_id');
        if (!$selectedAssistantId || !$assistants->contains('id', $selectedAssistantId)) {
            $selectedAssistantId = $assistants->isNotEmpty() ? $assistants->first()->id : 0;
        }

        // Busca apenas os departamentos DO ASSISTENTE SELECIONADO
        $departments = DB::table('departments')
            ->where('assistant_id', $selectedAssistantId)
            ->orderBy('name', 'asc')
            ->get();
        
        // Busca apenas os agentes que pertencem aos departamentos carregados
        $deptIds = $departments->pluck('id');
        $agents = DB::table('human_agents')
            ->whereIn('department_id', $deptIds)
            ->orderBy('name', 'asc')
            ->get();
        
        $currentView = 'equipe';

        return view('agents.index', compact('departments', 'agents', 'assistants', 'currentView', 'selectedAssistantId', 'statusFilter'));
    }

    private function storeDepartment(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $statusFilter = $request->input('status', 'ativo');
        $request->validate(['name' => 'required|string|max:255']);

        // Força maiúscula no Back-end garantindo consistência
        $departmentName = mb_strtoupper(trim($request->name), 'UTF-8');

        DB::table('departments')->insert([
            'assistant_id' => $assistantId,
            'name' => $departmentName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('success', 'Departamento criado com sucesso!');
    }

    private function updateDepartment(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $statusFilter = $request->input('status', 'ativo');
        $request->validate([
            'department_id' => 'required|integer',
            'name' => 'required|string|max:255'
        ]);
        
        // Força maiúscula na atualização
        $departmentName = mb_strtoupper(trim($request->name), 'UTF-8');

        DB::table('departments')->where('id', $request->department_id)->update([
            'name' => $departmentName,
            'updated_at' => now(),
        ]);
        
        return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('success', 'Departamento atualizado!');
    }

    private function deleteDepartment(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $statusFilter = $request->input('status', 'ativo');
        $deptId = (int)$request->input('department_id');
        
        DB::table('human_agents')->where('department_id', $deptId)->delete();
        DB::table('departments')->where('id', $deptId)->delete();
        
        return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('success', 'Departamento excluído!');
    }

    private function storeAgent(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $statusFilter = $request->input('status', 'ativo');
        $departmentId = (int) $request->input('department_id');
        $email = strtolower(trim((string)$request->input('email')));
        $name = trim((string)$request->input('name'));

        if (!$departmentId || !$email || !$name) {
            return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('error', 'Preencha todos os campos do agente.');
        }

        $duplicate = DB::table('human_agents')
            ->where('department_id', $departmentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('error', "Bloqueado: O e-mail '{$email}' já está em uso por '{$duplicate->name}'.");
        }

        DB::table('human_agents')->insert([
            'department_id' => $departmentId,
            'name' => $name,
            'email' => $email,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('success', "Agente '{$name}' adicionado com sucesso!");
    }

    private function updateAgent(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $statusFilter = $request->input('status', 'ativo');
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
            return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('error', "Bloqueado: O e-mail '{$email}' já pertence a outro agente.");
        }

        DB::table('human_agents')->where('id', $agentId)->update([
            'name' => $name,
            'email' => $email,
            'updated_at' => now(),
        ]);

        return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('success', "Agente '{$name}' atualizado!");
    }

    private function deleteAgent(Request $request)
    {
        $assistantId = (int)$request->input('assistant_id');
        $statusFilter = $request->input('status', 'ativo');
        $agentId = (int)$request->input('agent_id');
        
        DB::table('human_agents')->where('id', $agentId)->delete();
        
        return redirect("/?view=equipe&status={$statusFilter}&assistant_id={$assistantId}")->with('success', 'Agente excluído com sucesso!');
    }
}