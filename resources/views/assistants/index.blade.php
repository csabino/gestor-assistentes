<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Assistentes AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="{ view: 'card' }">
    
    <!-- Header Principal -->
    <nav class="bg-indigo-600 text-white shadow-sm">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="font-bold text-lg flex items-center gap-2">
                🤖 Gestor de Assistentes AI
            </div>
            <span class="text-xs bg-indigo-500 text-indigo-100 px-3 py-1 rounded-full font-medium">v1.0</span>
        </div>
    </nav>

    <div class="container mx-auto mt-6 px-4 max-w-5xl">
        
        <!-- Mensagem de Sucesso -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-lg mb-6 text-sm flex items-center gap-2 shadow-sm">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        <!-- Banner Superior: Formulário Compacto + Alternador de Visualização -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            
            <!-- Criar Assistente (Versão Enxuta/Inline) -->
            <form action="/" method="POST" class="flex items-center gap-2 w-full md:w-auto flex-1 max-w-lg">
                @csrf
                <input type="text" name="name" placeholder="Nome do assistente (ex: Vânia - Vendas)" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-1.5 px-4 rounded-lg transition shadow-sm whitespace-nowrap">
                    + Criar
                </button>
            </form>

            <!-- Botões de Alternar Visualização (Cards / Lista) -->
            <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg border border-gray-200 self-end md:self-auto">
                <button @click="view = 'card'" 
                    :class="view === 'card' ? 'bg-white shadow-sm text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-3 py-1 text-xs rounded-md transition flex items-center gap-1">
                    🔲 Cards
                </button>
                <button @click="view = 'list'" 
                    :class="view === 'list' ? 'bg-white shadow-sm text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-3 py-1 text-xs rounded-md transition flex items-center gap-1">
                    ☰ Lista
                </button>
            </div>
        </div>

        <!-- Título da Seção -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-gray-700">Assistentes Cadastrados ({{ $assistants->count() }})</h2>
        </div>
        
        <!-- VISUALIZAÇÃO EM CARDS -->
        <div x-show="view === 'card'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($assistants as $assistant)
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:border-indigo-300 transition flex flex-col justify-between gap-4">
                    <div class="flex justify-between items-start gap-2">
                        <h3 class="font-bold text-gray-800 truncate text-base">{{ $assistant->name }}</h3>
                        
                        <!-- Botão Alternar Status -->
                        <form action="{{ route('assistants.toggle', $assistant) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs px-2.5 py-1 rounded-full font-semibold transition border {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                                {{ $assistant->is_active ? '🟢 Ativo' : '🔴 Inativo' }}
                            </button>
                        </form>
                    </div>

                    <div class="flex items-center justify-between border-t pt-3 gap-2">
                        <button class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium py-1.5 px-3 rounded-lg text-xs transition flex-1 text-center">
                            ⚙️ Configurar
                        </button>
                        
                        <!-- Botão Deletar -->
                        <form action="{{ route('assistants.destroy', $assistant) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este assistente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 p-1.5 rounded-lg transition text-xs" title="Excluir">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                    <p class="text-sm">Nenhum assistente cadastrado ainda.</p>
                </div>
            @endforelse
        </div>

        <!-- VISUALIZAÇÃO EM LISTA -->
        <div x-show="view === 'list'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Nome</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assistants as $assistant)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-4 font-bold text-gray-800">{{ $assistant->name }}</td>
                            <td class="py-3 px-4">
                                <form action="{{ route('assistants.toggle', $assistant) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs px-2.5 py-0.5 rounded-full font-semibold transition border {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                                        {{ $assistant->is_active ? '🟢 Ativo' : '🔴 Inativo' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right flex justify-end items-center gap-2">
                                <button class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium py-1 px-3 rounded-lg text-xs transition">
                                    ⚙️ Configurar
                                </button>
                                <form action="{{ route('assistants.destroy', $assistant) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este assistente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 p-1 rounded-lg transition text-xs" title="Excluir">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-8 text-gray-400">Nenhum assistente cadastrado ainda.</td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>