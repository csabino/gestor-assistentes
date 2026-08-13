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
    
    <nav class="bg-indigo-600 text-white shadow-sm">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="font-bold text-lg flex items-center gap-2.5 hover:text-indigo-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" />
                </svg>
                Gestor de Assistentes AI
            </a>
            <span class="text-xs bg-indigo-500 px-3 py-1 rounded-full font-medium border border-indigo-400">Multi-Model</span>
        </div>
    </nav>

    <div class="container mx-auto mt-6 px-4 max-w-5xl mb-12">
        
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2 shadow-sm">
                ✅ {{ session('success') }}
            </div>
        @endif

        <!-- TELA DE CONFIGURAÇÃO DO ASSISTENTE -->
        @if($configuring)
            <div class="mb-4 flex items-center justify-between">
                <a href="/" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 text-sm">
                    &larr; Voltar para listagem
                </a>
                
                <!-- Botão Toggle Ativo/Inativo na Configuração -->
                <form action="/" method="POST">
                    @csrf @method('PATCH')
                    <input type="hidden" name="assistant_id" value="{{ $configuring->id }}">
                    <input type="hidden" name="from_config" value="1">
                    <button type="submit" class="text-sm px-4 py-1.5 rounded-full font-semibold transition border flex items-center gap-2 {{ $configuring->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                        @if($configuring->is_active)
                            <svg class="w-2.5 h-2.5 fill-emerald-500" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> IA Ativa
                        @else
                            <svg class="w-2.5 h-2.5 fill-gray-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> IA Inativa
                        @endif
                    </button>
                </form>
            </div>

            <!-- Formulário Dinâmico -->
            <form action="/" method="POST" enctype="multipart/form-data" x-data="{ provider: '{{ $configuring->provider ?? 'openai' }}' }">
                @csrf
                @method('PUT')
                <input type="hidden" name="assistant_id" value="{{ $configuring->id }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- COLUNA ESQUERDA: Cérebro da IA -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">🧠 Personalidade (Prompt)</h2>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Instruções do Sistema</label>
                            <p class="text-xs text-gray-500 mb-2">Descreva como a IA deve se comportar, qual o tom de voz e as regras de atendimento.</p>
                            <textarea name="system_prompt" rows="8" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Ex: Você é um vendedor especializado na loja X. Seja cordial, use emojis e nunca prometa descontos sem autorização...">{{ $configuring->system_prompt }}</textarea>
                        </div>

                        <!-- BASE DE CONHECIMENTO (Uploads) -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">📚 Base de Conhecimento</h2>
                            
                            <!-- Lista de Arquivos -->
                            @if($configuring->knowledge_files)
                                <div class="mb-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Documentos Anexados:</h4>
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        @foreach($configuring->knowledge_files as $file)
                                            <li class="flex items-center gap-2">📄 {{ $file['name'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <label class="block text-sm font-semibold text-gray-700 mb-1">Anexar Novo Arquivo</label>
                            <p class="text-xs text-gray-500 mb-3">Envie arquivos PDF, Word ou TXT para a IA consultar antes de responder.</p>
                            <input type="file" name="document" accept=".pdf,.doc,.docx,.txt" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                    </div>

                    <!-- COLUNA DIREITA: Conexão e Modelos -->
                    <div class="space-y-6">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">🔌 Conexão IA</h2>
                            
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Provedor</label>
                            <select name="provider" x-model="provider" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4 focus:ring-2 focus:ring-indigo-500">
                                <option value="openai">OpenAI (ChatGPT)</option>
                                <option value="gemini">Google (Gemini)</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="grok">xAI (Grok)</option>
                            </select>

                            <!-- Campos Dinâmicos OPENAI -->
                            <div x-show="provider === 'openai'" x-transition>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo da OpenAI</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                    <option value="gpt-4o-mini" {{ $configuring->model == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Rápido/Barato)</option>
                                    <option value="gpt-4o" {{ $configuring->model == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Avançado)</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">OpenAI API Key</label>
                                <input type="password" name="openai_api_key" value="{{ $configuring->openai_api_key }}" placeholder="sk-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                            </div>

                            <!-- Campos Dinâmicos GEMINI -->
                            <div x-show="provider === 'gemini'" x-transition style="display: none;">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Google</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                    <option value="gemini-1.5-flash" {{ $configuring->model == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash</option>
                                    <option value="gemini-1.5-pro" {{ $configuring->model == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Gemini API Key</label>
                                <input type="password" name="gemini_api_key" value="{{ $configuring->gemini_api_key }}" placeholder="AIza..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                            </div>

                            <!-- Campos Dinâmicos CLAUDE -->
                            <div x-show="provider === 'anthropic'" x-transition style="display: none;">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Claude</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                    <option value="claude-3-haiku" {{ $configuring->model == 'claude-3-haiku' ? 'selected' : '' }}>Claude 3 Haiku</option>
                                    <option value="claude-3.5-sonnet" {{ $configuring->model == 'claude-3.5-sonnet' ? 'selected' : '' }}>Claude 3.5 Sonnet</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Anthropic API Key</label>
                                <input type="password" name="anthropic_api_key" value="{{ $configuring->anthropic_api_key }}" placeholder="sk-ant-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                            </div>

                            <!-- Campos Dinâmicos GROK -->
                            <div x-show="provider === 'grok'" x-transition style="display: none;">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Grok</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                    <option value="grok-2-mini" {{ $configuring->model == 'grok-2-mini' ? 'selected' : '' }}>Grok 2 Mini</option>
                                    <option value="grok-2" {{ $configuring->model == 'grok-2' ? 'selected' : '' }}>Grok 2</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">xAI API Key</label>
                                <input type="password" name="grok_api_key" value="{{ $configuring->grok_api_key }}" placeholder="xai-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                            </div>
                        </div>

                        <!-- Botão Salvar -->
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-md transition text-lg">
                            💾 Salvar Configurações
                        </button>
                    </div>
                </div>
            </form>

        <!-- TELA DE LISTAGEM (Ocultada quando em Configuração) -->
        @else
            <!-- TODO O CÓDIGO DA LISTAGEM DOS CARDS/LISTA FICA AQUI (Mantive intacto) -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <form action="/" method="POST" class="flex items-center gap-2 w-full md:w-auto flex-1 max-w-lg">
                    @csrf
                    <input type="text" name="name" placeholder="Nome do assistente (ex: Vânia - Vendas)" required class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-1.5 px-4 rounded-lg transition">Criar</button>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($assistants as $assistant)
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 hover:border-indigo-300 transition">
                        <div class="flex justify-between items-start gap-2 mb-4">
                            <h3 class="font-bold text-gray-800 text-base">{{ $assistant->name }}</h3>
                            <form action="/" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                <button type="submit" class="text-xs px-2.5 py-1 rounded-full font-semibold border {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-300' }}">
                                    {{ $assistant->is_active ? '🟢 Ativo' : '⚪ Inativo' }}
                                </button>
                            </form>
                        </div>
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                            <!-- BOTÃO DE CONFIGURAR -->
                            <a href="/?configure={{ $assistant->id }}" class="bg-gray-50 hover:bg-indigo-50 border hover:border-indigo-200 hover:text-indigo-700 text-gray-600 font-medium py-1.5 px-3 rounded-lg text-xs transition flex-1 text-center">
                                ⚙️ Configurar
                            </a>
                            <form action="/" method="POST" onsubmit="return confirm('Excluir assistente?');">
                                @csrf @method('DELETE')
                                <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                <button type="submit" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition text-xs">🗑️</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>