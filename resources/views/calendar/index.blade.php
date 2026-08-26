<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário e Horários</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234F46E5' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008z'/></svg>">
    
    <!-- FULLCALENDAR SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        /* Ajustes do FullCalendar para casar com Tailwind */
        .fc-theme-standard td, .fc-theme-standard th { border-color: #e2e8f0; }
        .fc-col-header-cell { background-color: #f8fafc; padding: 0.5rem 0; font-weight: 700; color: #334155; text-transform: uppercase; font-size: 0.75rem; }
        .fc-timegrid-slot-label { font-size: 0.75rem; color: #94a3b8; font-weight: 600; }
        .fc .fc-timegrid-slot-minor { border-top-style: dashed; }
        .fc-event { cursor: pointer; }
        
        /* Destaque para o Dia de Hoje (Fundo azul suave) */
        .fc .fc-day-today { background-color: #eef2ff !important; }
        
        /* Customizando a linha do horário atual (Vermelha) */
        .fc-theme-standard .fc-timegrid-now-indicator-line { border-color: #ef4444; border-width: 2px; }
        .fc-theme-standard .fc-timegrid-now-indicator-arrow { border-color: #ef4444; border-width: 6px; margin-top: -6px; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 min-h-screen flex overflow-hidden" 
    x-data="{ sidebarOpen: localStorage.getItem('sidebar_open') !== 'false' }"
>
    <!-- SIDEBAR LATERAL GLOBAL -->
    <aside class="bg-indigo-700 text-white min-h-screen transition-all duration-300 flex flex-col justify-between shrink-0 shadow-xl relative z-50" :class="sidebarOpen ? 'w-64' : 'w-20'">
        <div>
            <div class="h-16 flex items-center border-b border-indigo-600/80 transition-all px-3" :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <a href="/" class="font-bold text-lg flex items-center gap-3 truncate hover:text-indigo-200 transition" x-show="sidebarOpen">
                    <svg class="w-7 h-7 text-indigo-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" />
                    </svg>
                    <span class="truncate font-bold tracking-wide">Painel IA</span>
                </a>

                <button type="button" @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebar_open', sidebarOpen)" class="p-2 rounded-lg hover:bg-indigo-600 text-indigo-200 hover:text-white transition cursor-pointer flex items-center justify-center shrink-0" title="Alternar menu">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>
            </div>

            <nav class="p-3 space-y-2 font-medium text-sm">
                <div class="relative group">
                    <a href="/" class="flex items-center rounded-xl transition font-semibold text-indigo-100 hover:bg-indigo-600" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v1.242c0 .289.23.523.518.523h3.726c.288 0 .518-.234.518-.523V3.104M12 21v-3.75m9-4.5h-1.5M4.5 12.75H3m16.5 0a2.25 2.25 0 002.25-2.25V8.25a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 8.25v2.25a2.25 2.25 0 002.25 2.25h13.5z" /></svg>
                        <span x-show="sidebarOpen" class="truncate">Assistentes IA</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Assistentes IA</div>
                </div>

                <div class="relative group">
                    <a href="/?view=equipe" class="flex items-center rounded-xl transition font-semibold text-indigo-100 hover:bg-indigo-600" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        <span x-show="sidebarOpen" class="truncate">Equipe & Agendas</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Equipe & Agendas</div>
                </div>

                <div class="relative group">
                    <a href="/?view=agenda" class="flex items-center rounded-xl transition font-semibold bg-indigo-900/90 text-white shadow-sm border border-indigo-500/30" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008z" /></svg>
                        <span x-show="sidebarOpen" class="truncate">Calendário</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Calendário</div>
                </div>

                <div class="relative group">
                    <a href="/?view=settings" class="flex items-center rounded-xl transition font-semibold text-indigo-100 hover:bg-indigo-600" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.27 1.06-.12 1.451l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.27-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.149-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Settings</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Settings</div>
                </div>
            </nav>
        </div>

        <div class="p-4 border-t border-indigo-600/80 mt-auto">
            <span x-show="sidebarOpen" class="text-[11px] bg-indigo-800 text-indigo-200 px-3 py-1.5 rounded-full font-bold border border-indigo-500 block text-center shadow-inner tracking-wider">Multiagents v3.1</span>
            <span x-show="!sidebarOpen" class="text-[10px] text-indigo-300 font-bold block text-center tracking-widest">v2.0</span>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <div class="container mx-auto px-6 max-w-6xl py-6 flex flex-col h-full">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Calendário e Horários</h1>
                    <p class="text-xs text-slate-500 mt-1">Gerencie a disponibilidade e as reuniões agendadas de cada membro.</p>
                </div>

                <form id="calendarFilter" method="GET" action="/" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="view" value="agenda">
                    
                    <div class="flex items-center gap-2 border-r border-slate-200 pr-3">
                        <span class="font-bold text-indigo-600 uppercase text-[10px] tracking-wide">Status:</span>
                        <select name="status" onchange="document.querySelector('[name=assistant_id]').value=''; document.querySelector('[name=agent_id]').value='all'; this.form.submit()" class="font-bold text-slate-700 bg-transparent focus:outline-none cursor-pointer text-xs">
                            <option value="ativo" {{ $statusFilter == 'ativo' ? 'selected' : '' }}>Ativos</option>
                            <option value="inativo" {{ $statusFilter == 'inativo' ? 'selected' : '' }}>Inativos</option>
                            <option value="todos" {{ $statusFilter == 'todos' ? 'selected' : '' }}>Todos</option>
                        </select>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs flex items-center gap-2 shadow-sm">
                        <span class="font-bold text-indigo-600 uppercase text-[10px]">Assistente IA:</span>
                        <select name="assistant_id" onchange="document.querySelector('[name=agent_id]').value='all'; this.form.submit()" class="font-bold text-slate-700 bg-transparent focus:outline-none cursor-pointer">
                            @forelse($assistants as $ast)
                                <option value="{{ $ast->id }}" {{ $currentAssistantId == $ast->id ? 'selected' : '' }}>{{ $ast->name }}</option>
                            @empty
                                <option value="">Nenhum assistente</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs flex items-center gap-2 shadow-sm">
                        <span class="font-bold text-indigo-600 uppercase text-[10px]">AGENTE:</span>
                        <select name="agent_id" onchange="this.form.submit()" class="font-bold text-slate-700 bg-transparent focus:outline-none cursor-pointer max-w-[200px] truncate">
                            <option value="all" {{ $currentAgentId === 'all' ? 'selected' : '' }}>Todos os Agentes</option>
                            @foreach($agents as $ag)
                                <option value="{{ $ag->id }}" {{ (string)$currentAgentId === (string)$ag->id ? 'selected' : '' }}>{{ $ag->name }} ({{ $ag->department_name }})</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-col flex-1 min-h-0">

                <!-- CONTROLES DO FULLCALENDAR CONSTRUÍDOS EM TAILWIND -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 shrink-0 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="inline-flex rounded-lg border border-indigo-200 shadow-sm">
                            <button id="btn-prev" class="bg-indigo-600 text-white px-3 py-1.5 rounded-l-lg hover:bg-indigo-700 transition">&lt;</button>
                            <button id="btn-next" class="bg-indigo-600 text-white px-3 py-1.5 rounded-r-lg hover:bg-indigo-700 transition">&gt;</button>
                        </div>
                        <button id="btn-today" class="bg-indigo-300 hover:bg-indigo-400 text-indigo-900 font-semibold px-4 py-1.5 rounded-lg text-xs transition shadow-sm">Hoje</button>
                    </div>

                    <!-- Título Dinâmico do Calendário -->
                    <h2 id="cal-title" class="text-xl font-bold text-slate-800 tracking-wide capitalize">Carregando...</h2>

                    <div class="inline-flex rounded-lg border border-indigo-200 shadow-sm text-xs font-semibold">
                        <button id="btn-month" class="bg-indigo-600 text-white px-3 py-1.5 rounded-l-lg transition">Mês</button>
                        <button id="btn-week" class="bg-indigo-600 text-white px-3 py-1.5 transition">Semana</button>
                        <button id="btn-day" class="bg-indigo-600 text-white px-3 py-1.5 rounded-r-lg transition">Dia</button>
                    </div>
                </div>

                <!-- CONTAINER DO FULLCALENDAR -->
                <div class="flex-1 min-h-0 relative">
                    <div id="calendar" class="h-full"></div>
                </div>

            </div>
        </div>
    </main>

    <!-- SCRIPT DE INICIALIZAÇÃO DO FULLCALENDAR -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const agentId = '{{ $currentAgentId }}';
            const csrfToken = '{{ csrf_token() }}';

            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                height: '100%',
                initialView: 'timeGridWeek',
                headerToolbar: false, // Desliga a barra padrão porque criamos a nossa no HTML
                allDaySlot: false,
                slotMinTime: '00:00:00',
                slotMaxTime: '24:00:00',
                editable: true,
                selectable: true,
                
                // FEATURE: Indicador de horário atual (Linha Vermelha)
                nowIndicator: true, 
                scrollTimeReset: false,
                
                events: '/?action=get_events&agent_id=' + agentId,

                // Atualiza o título e faz o scroll automático
                datesSet: function(info) {
                    document.getElementById('cal-title').innerText = info.view.title;
                    
                    document.querySelectorAll('#btn-month, #btn-week, #btn-day').forEach(b => {
                        b.classList.remove('bg-indigo-800');
                        b.classList.add('bg-indigo-600');
                    });
                    
                    if (info.view.type === 'dayGridMonth') document.getElementById('btn-month').classList.add('bg-indigo-800', 'bg-indigo-600');
                    if (info.view.type === 'timeGridWeek') document.getElementById('btn-week').classList.add('bg-indigo-800', 'bg-indigo-600');
                    if (info.view.type === 'timeGridDay') document.getElementById('btn-day').classList.add('bg-indigo-800', 'bg-indigo-600');

                    // Lógica de Scroll e Centralização Dinâmica
                    setTimeout(() => {
                        if (info.view.type === 'dayGridMonth') {
                            // Rola a visão mensal para deixar o dia atual visível no centro
                            const todayEl = document.querySelector('.fc-day-today');
                            if (todayEl) {
                                todayEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        } else {
                            // Rola a visão semanal/diária para o horário de agora (Dando margem de 1h)
                            const now = new Date();
                            now.setHours(now.getHours() - 1); // 1 hora pra cima
                            const timeStr = now.getHours().toString().padStart(2, '0') + ':00:00';
                            calendar.scrollToTime(timeStr);
                        }
                    }, 50);
                },

                select: function(info) {
                    if (agentId === 'all') {
                        alert('Selecione um agente específico no filtro acima para poder criar um bloqueio.');
                        calendar.unselect();
                        return;
                    }
                    if (confirm('Criar BLOQUEIO neste horário?')) {
                        fetch('/', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({
                                action: 'store_event',
                                human_agent_id: agentId,
                                start_time: info.startStr,
                                end_time: info.endStr,
                                type: 'block'
                            })
                        }).then(r => r.json()).then(data => {
                            if(data.success) calendar.refetchEvents();
                            else alert(data.message || 'Erro ao criar bloqueio.');
                        });
                    }
                },

                eventDrop: function(info) {
                    fetch('/', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            action: 'update_event',
                            id: info.event.id,
                            start_time: info.event.startStr,
                            end_time: info.event.endStr
                        })
                    }).then(r => r.json()).then(data => {
                        if(!data.success) info.revert();
                    });
                },

                eventResize: function(info) {
                    fetch('/', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            action: 'update_event',
                            id: info.event.id,
                            start_time: info.event.startStr,
                            end_time: info.event.endStr
                        })
                    }).then(r => r.json()).then(data => {
                        if(!data.success) info.revert();
                    });
                },

                eventClick: function(info) {
                    if (confirm('Deseja excluir este evento/bloqueio?')) {
                        fetch('/', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ action: 'delete_event', id: info.event.id })
                        }).then(r => r.json()).then(data => {
                            if(data.success) info.event.remove();
                        });
                    }
                }
            });

            calendar.render();

            // Interligando nossos botões Tailwind à API do Calendário
            document.getElementById('btn-prev').addEventListener('click', () => calendar.prev());
            document.getElementById('btn-next').addEventListener('click', () => calendar.next());
            document.getElementById('btn-today').addEventListener('click', () => calendar.today());
            document.getElementById('btn-month').addEventListener('click', () => calendar.changeView('dayGridMonth'));
            document.getElementById('btn-week').addEventListener('click', () => calendar.changeView('timeGridWeek'));
            document.getElementById('btn-day').addEventListener('click', () => calendar.changeView('timeGridDay'));
        });
    </script>
</body>
</html>