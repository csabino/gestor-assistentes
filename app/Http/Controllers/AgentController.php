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

        $departments = DB::table('departments')->orderBy('name', 'asc')->get();
        $agents = DB::table('department_members')->orderBy('name', 'asc')->get();
        $assistants = DB::table('assistants')->get();
        $currentView = 'equipe';

        return view('agents.index', compact('departments', 'agents', 'assistants', 'currentView'));
    }

    private function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        DB::table('departments')->insert([
            'name' => trim($request->name),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect('/?view=equipe')->with('success', 'Departamento criado com sucesso!');
    }

    private function updateDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|integer',
            'name' => 'required|string|max:255'
        ]);
        DB::table('departments')->where('id', $request->department_id)->update([
            'name' => trim($request->name),
            'updated_at' => now(),
        ]);
        return redirect('/?view=equipe')->with('success', 'Departamento atualizado com sucesso!');
    }

    private function deleteDepartment(Request $request)
    {
        $deptId = (int)$request->input('department_id');
        DB::table('department_members')->where('department_id', $deptId)->delete();
        DB::table('departments')->where('id', $deptId)->delete();
        return redirect('/?view=equipe')->with('success', 'Departamento excluído!');
    }

    private function storeAgent(Request $request)
    {
        $departmentId = (int) $request->input('department_id');
        $email = strtolower(trim((string)$request->input('email')));
        $name = trim((string)$request->input('name'));

        if (!$departmentId || !$email || !$name) {
            return redirect('/?view=equipe')->with('error', 'Preencha todos os campos do agente.');
        }

        $duplicate = DB::table('department_members')
            ->where('department_id', $departmentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect('/?view=equipe')->with('error', "Bloqueado: O e-mail '{$email}' já está cadastrado para '{$duplicate->name}' neste departamento.");
        }

        DB::table('department_members')->insert([
            'department_id' => $departmentId,
            'name' => $name,
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/?view=equipe')->with('success', "Agente '{$name}' adicionado com sucesso!");
    }

    private function updateAgent(Request $request)
    {
        $agentId = (int)$request->input('agent_id');
        $departmentId = (int)$request->input('department_id');
        $name = trim((string)$request->input('name'));
        $email = strtolower(trim((string)$request->input('email')));

        $duplicate = DB::table('department_members')
            ->where('department_id', $departmentId)
            ->where('id', '!=', $agentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect('/?view=equipe')->with('error', "Bloqueado: O e-mail '{$email}' já está em uso por outro agente neste mesmo departamento.");
        }

        DB::table('department_members')->where('id', $agentId)->update([
            'name' => $name,
            'email' => $email,
            'updated_at' => now(),
        ]);

        return redirect('/?view=equipe')->with('success', "Agente '{$name}' atualizado!");
    }

    private function deleteAgent(Request $request)
    {
        $agentId = (int)$request->input('agent_id');
        DB::table('department_members')->where('id', $agentId)->delete();
        return redirect('/?view=equipe')->with('success', 'Agente excluído com sucesso!');
    }
}