<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Assistentes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    
    <nav class="bg-indigo-600 p-4 text-white shadow-md">
        <div class="container mx-auto font-bold text-xl flex items-center gap-2">
            🤖 Gestor de Assistentes AI
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4 max-w-4xl">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
            <h2 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">➕ Novo Assistente</h2>
            <form action="{{ route('assistants.store') }}" method="POST" class="flex flex-col md:flex-row gap-4">
                @csrf
                <input type="text" name="name" placeholder="Nome do Assistente (ex: Vânia)" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-8 rounded-lg transition shadow-md">
                    Criar
                </button>
            </form>
        </div>

        <h2 class="text-xl font-bold mb-4 text-gray-700 border-b-2 border-indigo-100 inline-block pb-1">Meus Assistentes</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($assistants as $assistant)
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-indigo-500 flex justify-between items-center hover:shadow-md transition">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">{{ $assistant->name }}</h3>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold">🟢 Ativo</span>
                    </div>
                    <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg text-sm transition">
                        ⚙️ Configurar
                    </button>
                </div>
            @empty
                <div class="col-span-2 text-center py-10 text-gray-500 bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
                    <p class="text-lg mb-2">Nenhum assistente criado ainda.</p>
                    <p class="text-sm">Crie o seu primeiro assistente no formulário acima!</p>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>