<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipe e Agendas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="{ showDeptModal: false, showAgentModal: false, selectedDept: null }">
    
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
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestão de Equipes</h1>
                <p class="text-sm text-gray-500">Crie departamentos e gerencie os agentes humanos que a IA poderá acionar.</p>
            </div>
            <button @click="showDeptModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-5 rounded-lg transition shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Novo Departamento
            </button>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-lg mb-6 text-sm border border-emerald-200">✅ {{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                        </form>
                    </div>

                    <div class="p-4 flex-1">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Membros ({{ $dept->agents->count() }})</h3>
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
                                        <button type="submit" class="text-red-300 hover:text-red-600 opacity-0 group-hover:opacity-100 transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
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
                    <p class="text-gray-500 mb-2">Nenhum departamento cadastrado ainda.</p>
                    <button @click="showDeptModal = true" class="text-indigo-600 font-bold hover:underline">Criar o primeiro departamento</button>
                </div>
            @endforelse
        </div>

        <!-- MODAL DEPARTAMENTO -->
        <div x-show="showDeptModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div @click.away="showDeptModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Novo Departamento</h3>
                <form action="/?view=equipe" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="store_department">
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
                            <input type="email" name="email" placeholder="felipe@inhouse..." class="w-full border border-gray-300 rounded-lg p-2 text-sm">
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