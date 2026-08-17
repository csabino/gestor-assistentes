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
        $assistants = Assistant::with(['departments.agents'])->orderBy('name', 'asc')->get();

        if ($assistants->count() === 0) {
            return redirect('/')->with('error', 'Crie pelo menos um assistente/robô primeiro!');
        }

        $currentAssistantId = $request->input('assistant_id', session('last_agenda_ast_id', $assistants->first()->id));
        if (!$assistants->contains('id', $currentAssistantId)) {
            $currentAssistantId = $assistants->first()->id;
        }
        session(['last_agenda_ast_id' => $currentAssistantId]);

        $currentAssistant = $assistants->firstWhere('id', $currentAssistantId);

        $agents = collect();
        if ($currentAssistant) {
            foreach ($currentAssistant->departments as $dept) {
                foreach ($dept->agents as $ag) {
                    $ag->department_name = $dept->name;
                    $agents->push($ag);
                }
            }
        }

        $currentAgentId = $request->input('agent_id', session('last_agenda_agent_id'));
        if (!$agents->contains('id', $currentAgentId)) {
            $currentAgentId = $agents->first()->id ?? null;
        }
        if ($currentAgentId) {
            session(['last_agenda_agent_id' => $currentAgentId]);
        }

        return view('calendar.index', compact('assistants', 'currentAssistantId', 'agents', 'currentAgentId'));
    }

    private function getEvents(Request $request)
    {
        $agentId = $request->input('agent_id');
        if (!$agentId) return response()->json([]);

        $appointments = Appointment::where('human_agent_id', $agentId)->get();
        
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