<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use App\Models\HumanAgent;
use App\Models\Appointment;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function handle(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'get_events') return $this->getEvents($request);
        if ($action === 'store_event') return $this->storeEvent($request);
        if ($action === 'update_event') return $this->updateEvent($request);
        if ($action === 'delete_event') return $this->destroyEvent($request);

        return $this->index($request);
    }

    public function index(Request $request)
    {
        // 1. Filtro de Status (Padrão: ativo)
        $statusFilter = $request->input('status', 'ativo');

        // 2. Busca assistentes com base no status
        $assistantsQuery = Assistant::with(['departments.agents'])->orderBy('name', 'asc');
        
        if ($statusFilter === 'ativo') {
            $assistantsQuery->where('is_active', 1);
        } elseif ($statusFilter === 'inativo') {
            $assistantsQuery->where('is_active', 0);
        }
        
        $assistants = $assistantsQuery->get();

        // 3. Define o Assistente atual (Se vier vazio, pega o primeiro)
        $currentAssistantId = $request->input('assistant_id');
        
        if (!$currentAssistantId || !$assistants->contains('id', $currentAssistantId)) {
            $currentAssistantId = $assistants->isNotEmpty() ? $assistants->first()->id : null;
        }

        $currentAssistant = $assistants->firstWhere('id', $currentAssistantId);

        // 4. Popula os Agentes baseados no Assistente selecionado
        $agents = collect();
        if ($currentAssistant) {
            foreach ($currentAssistant->departments as $dept) {
                foreach ($dept->agents as $ag) {
                    $ag->department_name = $dept->name;
                    $agents->push($ag);
                }
            }
        }

        $agents = $agents->sortBy('name')->values();

        // 5. O padrão absoluto é 'all' (Todos os Agentes).
        $currentAgentId = $request->input('agent_id', 'all');
        if ($currentAgentId !== 'all' && !$agents->contains('id', $currentAgentId)) {
            $currentAgentId = 'all';
        }

        // Guarda o assistente selecionado pra API de eventos saber qual buscar se for "all"
        session(['last_agenda_ast_id' => $currentAssistantId]);

        return view('calendar.index', compact('assistants', 'currentAssistantId', 'agents', 'currentAgentId', 'statusFilter'));
    }

    private function getEvents(Request $request)
    {
        $agentId = $request->input('agent_id', 'all');
        
        $query = Appointment::query();
        
        if ($agentId !== 'all' && $agentId) {
            // Traz eventos de um agente específico
            $query->where('human_agent_id', $agentId);
        } else {
            // Traz eventos de TODOS os agentes do Assistente atual
            $astId = session('last_agenda_ast_id');
            if ($astId) {
                $ast = Assistant::with('departments.agents')->find($astId);
                $agentIds = [];
                if ($ast) {
                    foreach ($ast->departments as $dept) {
                        $agentIds = array_merge($agentIds, $dept->agents->pluck('id')->toArray());
                    }
                }
                $query->whereIn('human_agent_id', $agentIds);
            } else {
                return response()->json([]);
            }
        }

        $appointments = $query->get();
        
        $events = $appointments->map(function($app) {
            $isBlock = ($app->client_name === 'BLOQUEIO_MANUAL');
            return [
                'id' => $app->id,
                'title' => $isBlock ? '🚫 Indisponível' : "📅 {$app->client_name}",
                'start' => $app->start_time->format('Y-m-d\TH:i:s'),
                'end' => $app->end_time->format('Y-m-d\TH:i:s'),
                'backgroundColor' => $isBlock ? '#ef4444' : '#4f46e5',
                'borderColor' => $isBlock ? '#dc2626' : '#4338ca',
                'extendedProps' => [
                    'type' => $isBlock ? 'block' : 'appointment',
                    'client_name' => $app->client_name,
                    'client_email' => $app->client_email,
                    'client_phone' => $app->client_phone,
                    'status' => $app->status
                ]
            ];
        });

        return response()->json($events);
    }

    private function storeEvent(Request $request)
    {
        if ($request->human_agent_id === 'all') {
            return response()->json(['success' => false, 'message' => 'Selecione um Agente específico no filtro para poder agendar ou bloquear horário.']);
        }

        $request->validate([
            'human_agent_id' => 'required|exists:human_agents,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $type = $request->input('type', 'block');
        
        Appointment::create([
            'human_agent_id' => $request->human_agent_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'client_name' => $type === 'block' ? 'BLOQUEIO_MANUAL' : $request->client_name,
            'client_phone' => $type === 'block' ? '00000000000' : $request->client_phone,
            'client_email' => $type === 'block' ? 'bloqueio@interno' : $request->client_email,
            'status' => 'scheduled'
        ]);

        return response()->json(['success' => true]);
    }

    private function updateEvent(Request $request)
    {
        $app = Appointment::findOrFail($request->id);
        $app->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);
        return response()->json(['success' => true]);
    }

    private function destroyEvent(Request $request)
    {
        Appointment::findOrFail($request->id)->delete();
        return response()->json(['success' => true]);
    }
}