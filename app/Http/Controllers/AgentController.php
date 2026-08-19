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

        if ($request->isMethod('post') && $request->input('action') === 'store_agent') {
            return $this->storeAgent($request);
        }

        if ($request->isMethod('post') && $request->input('action') === 'store_department') {
            return $this->storeDepartment($request);
        }

        $departments = DB::table('departments')->get();
        $agents = DB::table('department_members')->get();
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

    private function storeAgent(Request $request)
    {
        $departmentId = (int) $request->input('department_id');
        $email = strtolower(trim((string)$request->input('email')));
        $name = trim((string)$request->input('name'));

        if (!$departmentId || !$email || !$name) {
            return redirect('/?view=equipe')->with('error', 'Preencha todos os campos do agente.');
        }

        // TRAVA RÍGIDA DE E-MAIL NO MESMO DEPARTAMENTO
        $duplicate = DB::table('department_members')
            ->where('department_id', $departmentId)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($duplicate) {
            return redirect('/?view=equipe')->with('error', "Bloqueado: O e-mail '{$email}' já está cadastrado para o agente '{$duplicate->name}' neste departamento.");
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
}