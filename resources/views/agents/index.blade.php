<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipe e Agendas | Gestor AI</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234F46E5' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="{ 
    view: localStorage.getItem('equipe_view') || 'card', 
    showDeptModal: false, 
    showAgentModal: false, 
    selectedDept: null 
}" x-init="$watch('view', value => localStorage.setItem('equipe_view', value))">
    
    <nav class="bg-indigo-600 text-white shadow-sm relative z-50">
        <div class="container mx-auto px-4 flex justify-between items-center h-14">
            <div class="flex items-center gap-6 h-full">
                <a href="/" class="font-bold text-lg flex items-center gap-2.5 hover:text-indigo-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                    Painel
                </a>
                
                <div class="h-full flex">
                    <a href="/" class="flex items-center px-3 text-sm font-medium border-b-2 border-transparent text-indigo-100 hover:text-white hover:border-indigo-300 transition">Robôs IA</a>
                    <a href="/?view=equipe" class="flex items-center px-3 text-sm font-medium border-b-2 border-white text-white">Equipe & Agendas</a>
                </div>
            </div>
            <span class="text-xs bg-indigo-500 px-3 py-1 rounded-full font-medium border border-indigo-400">Multi-Model</span>
        </div>
    </nav>

    <div class="container mx-auto px-4 max-w-6xl mt-8 mb-12">
        
        <!-- CABEÇALHO COM FILTRO E SELETOR DE ASSISTENTE -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestão de Equipes</h1>
                <p class="text-sm text-gray-500">Departamentos e agentes subordinados ao assistente selecionado.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3" 
                 x-data="{
                    astFilter: localStorage.getItem('equipe_ast_filter') || 'active',
                    currentAstId: {{ $currentAssistantId ?? 'null' }},
                    assistants: [
                        @foreach($assistants as $ast)
                            { id: {{ $ast->id }}, name: '{{ addslashes($ast->name) }}', is_active: {{ $ast->is_active ? 'true' : 'false' }} },
                        @endforeach
                    ],
                    get filteredAssistants() {
                        return this.assistants.filter(a => this.astFilter === 'all' || (this.astFilter === 'active' && a.is_active) || (this.astFilter === 'inactive' && !a.is_active));
                    }
                 }"
                 x-init="$watch('astFilter', val => localStorage.setItem('equipe_ast_filter', val))"
            >
                
                <!-- Filtro de Status do Robô -->
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Filtro:</span>
                    <select x-model="astFilter" class="text-xs font-bold text-gray-700 bg-transparent focus:outline-none cursor-pointer">
                        <option value="active">🟢 Ativos</option>
                        <option value="inactive">⚪ Inativos</option>
                        <option value="all">Todos</option>
                    </select>
                </div>

                <!-- Seletor do Robô (Assistente) Dinâmico com Trava Anti-Fantasma -->
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Robô:</span>
                    <select x-model="currentAstId" @change="if($event.isTrusted && $el.value) window.location.href = '/?view=equipe&assistant_id=' + $el.value" class="text-sm font-bold text-indigo-700 bg-transparent focus:outline-none cursor-pointer w-40">
                        <template x-for="ast in filteredAssistants" :key="ast.id">
                            <option :value="ast.id" x-text="ast.name + (ast.is_active ? '' : ' (Inativo)')"></option>
                        </template>
                        <template x-if="filteredAssistants.length === 0">
                            <option value="" disabled>Nenhum robô nesta lista</option>
                        </template>
                    </select>
                </div>

                <!-- Switcher Visão (Cards/Lista) -->
                <div class="flex items-center bg-gray-200/80 p-0.5 rounded-lg border border-gray-200 hidden sm:flex">
                    <button @click="view = 'card'" :class="view === 'card' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-1.5" title="Ver Cards">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    </button>
                    <button @click="view = 'list'" :class="view === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1.5 rounded-md text-xs font-bold transition flex items-center gap-1.5" title="Ver Lista">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M3.75 4.5h16.5" /></svg>
                    </button>
                </div>

                <!-- Botão Novo Depto -->
                <button @click="showDeptModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Novo Departamento
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-lg mb-6 text-sm border border-emerald-200 shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- VISÃO DE CARDS -->
        <div x-show="view === 'card'" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($departments as $dept)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-start">
                        <div>
                            <h2 class="font-bold text-gray-800 text-lg flex items-center gap-1.5">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                {{ $dept->name }}
                            </h2>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ $dept->description ?? 'Sem descrição' }}</p>
                        </div>
                        <form action="/?view=equipe" method="POST" onsubmit="return confirm('Excluir este departamento apagará todos os agentes dele. Confirma?');">
                            @csrf
                            <input type="hidden" name="action" value="delete_department">
                            <input type="hidden" name="department_id" value="{{ $dept->id }}">
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition p-1" title="Excluir Departamento"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </form>
                    </div>

                    <div class="p-4 flex-1">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Agentes ({{ $dept->agents->count() }})</h3>
                        <ul class="space-y-3">
                            @forelse($dept->agents as $agent)
                                <li class="flex items-center justify-between group">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs border border-indigo-100">
                                            {{ substr($agent->name, 0, 1) }}
                                        </div>
                                        <div class="leading-tight">
                                            <p class="text-sm font-semibold text-gray-800">{{ $agent->name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $agent->email ?? 'Sem e-mail' }}</p>
                                        </div>
                                    </div>
                                    <form action="/?view=equipe" method="POST" onsubmit="return confirm('Remover {{ $agent->name }}?');">
                                        @csrf
                                        <input type="hidden" name="action" value="delete_agent">
                                        <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                                        <button type="submit" class="text-red-300 hover:text-red-600 opacity-0 group-hover:opacity-100 transition p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                    </form>
                                </li>
                            @empty
                                <li class="text-xs text-gray-400 italic">Nenhum agente cadastrado.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="p-3 bg-gray-50 border-t border-gray-100">
                        <button @click="selectedDept = {{ $dept->id }}; showAgentModal = true" class="w-full py-1.5 text-xs font-bold text-indigo-600 bg-white border border-indigo-200 hover:bg-indigo-50 rounded-lg transition shadow-sm border-dashed">
                            + Adicionar Agente
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200 border-dashed">
                    <p class="text-gray-500 mb-2">Nenhum departamento vinculado a este assistente.</p>
                    <button @click="showDeptModal = true" class="text-indigo-600 font-bold hover:underline">Criar o primeiro departamento</button>
                </div>
            @endforelse
        </div>

        <!-- VISÃO DE LISTA (TABELA) -->
        <div x-show="view === 'list'" x-cloak x-transition class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="py-4 px-5 font-semibold w-1/4">Departamento</th>
                        <th class="py-4 px-5 font-semibold">Agentes Membros</th>
                        <th class="py-4 px-5 font-semibold text-right w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($departments as $dept)
                        <tr class="hover:bg-gray-50 transition duration-150 group">
                            <td class="py-4 px-5 align-top">
                                <h3 class="font-bold text-gray-800 text-base flex items-center gap-1.5">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    {{ $dept->name }}
                                </h3>
                                <p class="text-[11px] text-gray-500 mt-1">{{ $dept->description ?? 'Sem descrição' }}</p>
                                <button @click="selectedDept = {{ $dept->id }}; showAgentModal = true" class="text-xs text-indigo-600 font-bold mt-3 hover:underline flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Adicionar Agente
                                </button>
                            </td>
                            <td class="py-4 px-5 align-top">
                                <div class="flex flex-wrap gap-2">
                                    @forelse($dept->agents as $agent)
                                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg p-1.5 shadow-sm pr-2 gap-3 group/agent w-auto">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-[10px] border border-indigo-100">
                                                    {{ substr($agent->name, 0, 1) }}
                                                </div>
                                                <div class="leading-none">
                                                    <span class="text-xs font-semibold text-gray-800 block">{{ $agent->name }}</span>
                                                </div>
                                            </div>
                                            <form action="/?view=equipe" method="POST" onsubmit="return confirm('Remover {{ $agent->name }}?');">
                                                @csrf
                                                <input type="hidden" name="action" value="delete_agent">
                                                <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                                                <button type="submit" class="text-gray-300 hover:text-red-500 bg-gray-50 hover:bg-red-50 rounded p-1 transition opacity-0 group-hover/agent:opacity-100">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <span class="text-xs text-gray-400 italic mt-1">Nenhum membro neste departamento.</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-4 px-5 align-top text-right">
                                <form action="/?view=equipe" method="POST" onsubmit="return confirm('Excluir departamento?');">
                                    @csrf
                                    <input type="hidden" name="action" value="delete_department">
                                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                    <button type="submit" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition" title="Excluir Departamento">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-12 text-gray-400">Nenhum departamento cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- MODAL DEPARTAMENTO -->
        <div x-show="showDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div @click.away="showDeptModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Novo Departamento</h3>
                <form action="/?view=equipe" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="store_department">
                    <input type="hidden" name="assistant_id" value="{{ $currentAssistantId }}">
                    
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome do Departamento</label>
                    <input type="text" name="name" required placeholder="Ex: Comercial, Suporte Técnico..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                    
                    <label class="block text-xs font-bold text-gray-700 mb-1">Descrição (Opcional)</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg p-2 text-sm mb-5"></textarea>
                    
                    <div class="flex gap-2 justify-end border-t border-gray-100 pt-4">
                        <button type="button" @click="showDeptModal = false" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-5 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg shadow-sm">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL AGENTE -->
        <div x-show="showAgentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div @click.away="showAgentModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    Adicionar Agente
                </h3>
                <form action="/?view=equipe" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="store_agent">
                    <input type="hidden" name="department_id" :value="selectedDept">
                    
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome Completo</label>
                    <input type="text" name="name" required placeholder="Ex: Felipe Alves" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                    
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">E-mail (Opcional)</label>
                            <input type="email" name="email" placeholder="felipe@email..." class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Telefone (Opcional)</label>
                            <input type="text" name="phone" placeholder="11 9999-9999" class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                        </div>
                    </div>
                    
                    <div class="flex gap-2 justify-end border-t border-gray-100 pt-4">
                        <button type="button" @click="showAgentModal = false" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-5 py-2 text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg shadow-sm">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>