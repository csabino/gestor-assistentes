<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário | Gestor AI</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234F46E5' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales-all.global.min.js'></script>
    <style>
        [x-cloak] { display: none !important; }
        .fc .fc-button-primary { background-color: #4f46e5 !important; border-color: #4f46e5 !important; }
        .fc .fc-button-primary:hover { background-color: #4338ca !important; border-color: #4338ca !important; }
        .fc-event { cursor: pointer; transition: 0.2s; }
        .fc-event:hover { opacity: 0.9; }
        /* Mantém o calendário preenchendo a área livre sem estourar a tela */
        .fc { height: 100% !important; display: flex; flex-direction: column; }
        .fc .fc-view-harness { flex: 1 1 auto; height: 100% !important; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 h-screen flex flex-col overflow-hidden">
    
    <!-- NAVBAR (FIXO) -->
    <nav class="bg-indigo-600 text-white shadow-sm relative z-50 shrink-0">
        <div class="container mx-auto px-4 flex justify-between items-center h-14">
            <div class="flex items-center gap-6 h-full">
                <a href="/" class="font-bold text-lg flex items-center gap-2.5 hover:text-indigo-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                    Painel
                </a>
                <div class="h-full flex">
                    <a href="/" class="flex items-center px-3 text-sm font-medium border-b-2 border-transparent text-indigo-100 hover:text-white transition">Robôs IA</a>
                    <a href="/?view=equipe" class="flex items-center px-3 text-sm font-medium border-b-2 border-transparent text-indigo-100 hover:text-white transition">Equipe & Agendas</a>
                    <a href="/?view=agenda" class="flex items-center px-3 text-sm font-medium border-b-2 border-white text-white">Calendário</a>
                </div>
            </div>
            <span class="text-xs bg-indigo-500 px-3 py-1 rounded-full font-medium border border-indigo-400">Multi-Model</span>
        </div>
    </nav>

    <!-- CONTEÚDO PRINCIPAL COM ALTURA AJUSTADA -->
    <div class="container mx-auto px-4 max-w-6xl pt-6 pb-4 flex-1 flex flex-col overflow-hidden">
        
        <!-- CABEÇALHO DA PÁGINA E CONTROLES (FIXO NO TOPO) -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4 shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Calendário e Horários</h1>
                <p class="text-xs text-gray-500">Gerencie a disponibilidade e as reuniões agendadas de cada membro.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Robô:</span>
                    <select onchange="window.location.href='/?view=agenda&assistant_id=' + this.value" class="text-sm font-bold text-indigo-700 bg-transparent focus:outline-none cursor-pointer w-32">
                        @foreach($assistants as $ast)
                            <option value="{{ $ast->id }}" {{ $currentAssistantId == $ast->id ? 'selected' : '' }}>{{ $ast->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Agente:</span>
                    <select onchange="window.location.href='/?view=agenda&assistant_id={{ $currentAssistantId }}&agent_id=' + this.value" class="text-sm font-bold text-indigo-700 bg-transparent focus:outline-none cursor-pointer w-48">
                        @forelse($agents as $agent)
                            <option value="{{ $agent->id }}" {{ $currentAgentId == $agent->id ? 'selected' : '' }}>{{ $agent->name }} ({{ $agent->department_name }})</option>
                        @empty
                            <option value="" disabled>Nenhum agente cadastrado</option>
                        @endforelse
                    </select>
                </div>
            </div>
        </div>

        <!-- CONTAINER DO CALENDÁRIO (CRAVADO NO ESPAÇO RESTANTE) -->
        @if($currentAgentId)
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex-1 flex flex-col overflow-hidden">
                <p class="text-[11px] text-gray-400 mb-2 font-semibold uppercase tracking-wider shrink-0">
                    <svg class="w-3.5 h-3.5 inline text-indigo-500 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Dica: Clique em qualquer lugar vazio para agendar ou criar bloqueio. Arraste os eventos para mudar o horário.
                </p>
                
                <!-- ÁREA INTERNA DO CALENDÁRIO QUE FAZ A ROLAGEM DOS HORÁRIOS -->
                <div id="calendar" class="flex-1 min-h-0"></div>
            </div>
        @else
            <div class="text-center py-16 bg-white rounded-xl border border-gray-200 border-dashed shrink-0">
                <p class="text-gray-500 mb-2">Este robô ainda não possui agentes humanos cadastrados.</p>
                <a href="/?view=equipe&assistant_id={{ $currentAssistantId }}" class="text-indigo-600 font-bold hover:underline">Ir para Gestão de Equipes</a>
            </div>
        @endif
    </div>

    <!-- CONTROLADOR ALPINE PARA MODAIS AJAX -->
    <div x-data="{
        showCreate: false,
        showView: false,
        eventData: { start: '', end: '', type: 'block', client_name: '', client_phone: '', client_email: '' },
        viewData: {},
        
        async saveEvent() {
            let res = await fetch('/?view=agenda&action=store_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ human_agent_id: '{{ $currentAgentId }}', ...this.eventData })
            });
            if(res.ok) {
                this.showCreate = false;
                window.calendarInstance.refetchEvents();
            } else {
                alert('Erro ao salvar. Verifique se preencheu os campos.');
            }
        },
        
        async deleteEvent() {
            if(!confirm('Excluir este evento do calendário?')) return;
            let res = await fetch('/?view=agenda&action=delete_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ id: this.viewData.id })
            });
            if(res.ok) {
                this.showView = false;
                window.calendarInstance.refetchEvents();
            }
        }
    }"
    @open-create-modal.window="eventData = { start: $event.detail.start, end: $event.detail.end, type: 'block', client_name: '', client_phone: '', client_email: '' }; showCreate = true;"
    @open-view-modal.window="viewData = $event.detail; showView = true;">

        <!-- MODAL DE CRIAÇÃO -->
        <div x-show="showCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div @click.away="showCreate = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Adicionar ao Calendário</h3>
                
                <div class="flex gap-4 mb-5 border-b border-gray-100 pb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="eventData.type" value="block" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-semibold text-gray-700">Bloqueio Manual</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="eventData.type" value="appointment" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-semibold text-gray-700">Agendar Cliente</span>
                    </label>
                </div>
                
                <div x-show="eventData.type === 'appointment'" class="space-y-3 mb-5">
                    <input type="text" x-model="eventData.client_name" placeholder="Nome do Cliente" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                    <input type="email" x-model="eventData.client_email" placeholder="E-mail" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                    <input type="text" x-model="eventData.client_phone" placeholder="Celular/WhatsApp" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>

                <div x-show="eventData.type === 'block'" class="mb-5 text-sm text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    A IA não marcará reuniões neste período.
                </div>
                
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="showCreate = false" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                    <button type="button" @click="saveEvent" class="px-5 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg shadow-sm">Salvar</button>
                </div>
            </div>
        </div>

        <!-- MODAL DE VISUALIZAÇÃO -->
        <div x-show="showView" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div @click.away="showView = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative">
                <button type="button" @click="showView = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 p-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                
                <h3 class="text-lg font-bold text-gray-800 mb-1" x-text="viewData.title"></h3>
                <p class="text-xs font-semibold text-gray-500 mb-5" x-text="viewData.start + ' até ' + viewData.end"></p>

                <div x-show="viewData.type === 'appointment'" class="space-y-2 mb-6 bg-indigo-50/50 p-4 rounded-lg border border-indigo-100 text-sm">
                    <p><b>E-mail:</b> <span x-text="viewData.client_email"></span></p>
                    <p><b>Telefone:</b> <span x-text="viewData.client_phone"></span></p>
                </div>

                <div class="flex gap-2 justify-end border-t border-gray-100 pt-4">
                    <button type="button" @click="deleteEvent" class="px-4 py-2 text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg border border-red-100 w-full">Cancelar / Excluir</button>
                </div>
            </div>
        </div>
    </div>

    @if($currentAgentId)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            if(!calendarEl) return;
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'timeGridWeek',
                height: '100%',
                stickyHeaderDates: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                slotMinTime: '07:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: false,
                selectable: true,
                editable: true,
                events: '/?view=agenda&action=get_events&agent_id={{ $currentAgentId }}',
                
                select: function(info) {
                    window.dispatchEvent(new CustomEvent('open-create-modal', { 
                        detail: { start: info.startStr, end: info.endStr }
                    }));
                },
                
                eventDrop: function(info) { updateEventTime(info.event); },
                eventResize: function(info) { updateEventTime(info.event); },
                
                eventClick: function(info) {
                    window.dispatchEvent(new CustomEvent('open-view-modal', { 
                        detail: { 
                            id: info.event.id,
                            title: info.event.title,
                            type: info.event.extendedProps.type,
                            client_name: info.event.extendedProps.client_name,
                            client_email: info.event.extendedProps.client_email,
                            client_phone: info.event.extendedProps.client_phone,
                            start: info.event.start.toLocaleString('pt-BR'),
                            end: info.event.end.toLocaleString('pt-BR')
                        }
                    }));
                }
            });
            calendar.render();
            window.calendarInstance = calendar;
        });

        function updateEventTime(event) {
            fetch('/?view=agenda&action=update_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ id: event.id, start_time: event.startStr, end_time: event.endStr })
            });
        }
    </script>
    @endif
</body>
</html>