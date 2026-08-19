<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Equipe - Multiagents</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234F46E5' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 min-h-screen flex overflow-hidden" 
    x-data="{ sidebarOpen: localStorage.getItem('sidebar_open') !== 'false' }"
>
    <!-- SIDEBAR LATERAL GLOBAL -->
    <aside class="bg-indigo-700 text-white min-h-screen transition-all duration-300 flex flex-col justify-between shrink-0 shadow-xl relative z-50" :class="sidebarOpen ? 'w-64' : 'w-20'">
        <div>
            <!-- MARCA E BOTÃO RETRÁTIL -->
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

            <!-- NAVEGAÇÃO COM TOOLTIPS -->
            <nav class="p-3 space-y-2 font-medium text-sm">
                <!-- ASSISTENTES IA -->
                <div class="relative group">
                    <a href="/" class="flex items-center rounded-xl transition font-semibold text-indigo-100 hover:bg-indigo-600" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v1.242c0 .289.23.523.518.523h3.726c.288 0 .518-.234.518-.523V3.104M12 21v-3.75m9-4.5h-1.5M4.5 12.75H3m16.5 0a2.25 2.25 0 002.25-2.25V8.25a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 8.25v2.25a2.25 2.25 0 002.25 2.25h13.5z" /></svg>
                        <span x-show="sidebarOpen" class="truncate">Assistentes IA</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50">Assistentes IA</div>
                </div>

                <!-- EQUIPE E AGENDAS (ATIVO AQUI) -->
                <div class="relative group">
                    <a href="/?view=equipe" class="flex items-center rounded-xl transition font-semibold bg-indigo-900/90 text-white shadow-sm border border-indigo-500/30" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        <span x-show="sidebarOpen" class="truncate">Equipe & Agendas</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50">Equipe & Agendas</div>
                </div>

                <!-- CALENDÁRIO -->
                <div class="relative group">
                    <a href="/?view=agenda" class="flex items-center rounded-xl transition font-semibold text-indigo-100 hover:bg-indigo-600" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008z" /></svg>
                        <span x-show="sidebarOpen" class="truncate">Calendário</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50">Calendário</div>
                </div>

                <!-- SETTINGS -->
                <div class="relative group">
                    <a href="/?view=settings" class="flex items-center rounded-xl transition font-semibold text-indigo-100 hover:bg-indigo-600" :class="sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center p-3'">
                        <svg class="w-5 h-5 shrink-0 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.27 1.06-.12 1.451l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.27-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.149-.894z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Settings</span>
                    </a>
                    <div x-show="!sidebarOpen" class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900 text-white text-[11px] font-bold rounded-md shadow-xl opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50">Settings</div>
                </div>
            </nav>
        </div>

        <!-- RODAPÉ DA SIDEBAR -->
        <div class="p-4 border-t border-indigo-600/80 mt-auto">
            <span x-show="sidebarOpen" class="text-[11px] bg-indigo-800 text-indigo-200 px-3 py-1.5 rounded-full font-bold border border-indigo-500 block text-center shadow-inner tracking-wider">Multiagents v2.0</span>
            <span x-show="!sidebarOpen" class="text-[10px] text-indigo-300 font-bold block text-center tracking-widest">v2.0</span>
        </div>
    </aside>

    <!-- CONTEÚDO PRINCIPAL DA TELA DE EQUIPE -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
        <div class="container mx-auto px-6 max-w-6xl py-8">
            
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- TELA DE GESTÃO DE EQUIPE E DEPARTAMENTOS -->
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-100 pb-3 mb-6">
                        <h1 class="text-xl font-bold text-gray-800">Gestão de Departamentos & Agentes</h1>
                        <p class="text-xs text-gray-500 mt-1">Gerencie os setores de atendimento e a lista de agentes associados.</p>
                    </div>

                    <!-- CRIAR DEPARTAMENTO -->
                    <form action="/?view=equipe" method="POST" class="mb-8 bg-gray-50 p-4 rounded-xl border border-gray-200">
                        @csrf
                        <input type="hidden" name="action" value="store_department">
                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wide mb-2">Novo Departamento</label>
                        <div class="flex gap-3">
                            <input type="text" name="name" required placeholder="Ex: Suporte Técnico, Vendas..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-lg text-xs transition shadow-sm">Criar Departamento</button>
                        </div>
                    </form>

                    <!-- LISTAGEM E ADIÇÃO DE AGENTES -->
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        @forelse($departments as $dept)
                            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-4">
                                <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                    <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 bg-indigo-500 rounded-full"></span>
                                        {{ $dept->name }}
                                    </h3>
                                </div>

                                <!-- FORMULÁRIO DE ADIÇÃO DE AGENTE -->
                                <form action="/?view=equipe" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
                                    @csrf
                                    <input type="hidden" name="action" value="store_agent">
                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nome do Agente</label>
                                        <input type="text" name="name" required placeholder="Ex: Carlos Silva" class="w-full border border-slate-300 rounded-md px-3 py-2 text-xs outline-none focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">E-mail Corporativo</label>
                                        <input type="email" name="email" required placeholder="carlos@empresa.com" class="w-full border border-slate-300 rounded-md px-3 py-2 text-xs outline-none focus:border-indigo-500">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-md text-xs transition shadow-sm">
                                            Adicionar Agente
                                        </button>
                                    </div>
                                </form>

                                <!-- TABELA DOS AGENTES DO DEPARTAMENTO -->
                                <div class="overflow-x-auto border border-gray-100 rounded-lg">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50 text-gray-500 border-b border-gray-100 uppercase text-[10px] tracking-wider">
                                                <th class="py-2.5 px-4 font-semibold">Agente</th>
                                                <th class="py-2.5 px-4 font-semibold">E-mail</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @forelse($agents->where('department_id', $dept->id) as $agent)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="py-3 px-4 font-bold text-gray-800">{{ $agent->name }}</td>
                                                    <td class="py-3 px-4 text-gray-600 font-mono">{{ $agent->email }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="py-5 text-center text-gray-400 italic">Nenhum agente cadastrado neste departamento.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-gray-400">Nenhum departamento cadastrado ainda.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>