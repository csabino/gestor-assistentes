<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Assistentes AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="{ 
    view: 'card', 
    filter: 'active',
    total: {{ $assistants->count() }},
    activeCount: {{ $assistants->where('is_active', true)->count() }},
    inactiveCount: {{ $assistants->where('is_active', false)->count() }},
    get currentCount() {
        if (this.filter === 'active') return this.activeCount;
        if (this.filter === 'inactive') return this.inactiveCount;
        return this.total;
    }
}">
    
    <!-- HEADER DA APLICAÇÃO -->
    <nav class="bg-indigo-600 text-white shadow-sm relative z-50">
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

    <div class="container mx-auto px-4 max-w-6xl mb-12">
        
        <!-- MENSAGEM DE SUCESSO -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg mt-6 text-sm flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-emerald-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- ==============================================
             TELA DE CONFIGURAÇÃO DO ASSISTENTE
             ============================================== -->
        @if($configuring)
            
            <!-- BARRA FIXA SUPERIOR (STICKY HEADER) -->
            <div class="sticky top-0 z-40 bg-gray-50/90 backdrop-blur-md py-4 mb-6 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 -mx-4 px-4 sm:mx-0 sm:px-0 shadow-sm mt-4">
                
                <div class="flex items-center gap-4">
                    <a href="/" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1.5 text-sm transition bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        Voltar
                    </a>
                    <div class="h-6 w-px bg-gray-300 hidden md:block"></div>
                    <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        {{ $configuring->name }}
                    </h1>
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <form action="/" method="POST" class="flex-1 md:flex-none">
                        @csrf @method('PATCH')
                        <input type="hidden" name="assistant_id" value="{{ $configuring->id }}">
                        <input type="hidden" name="from_config" value="1">
                        <button type="submit" class="w-full md:w-auto text-sm px-4 py-2 rounded-lg font-semibold transition border flex justify-center items-center gap-2 {{ $configuring->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                            @if($configuring->is_active)
                                <svg class="w-2.5 h-2.5 fill-emerald-500" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Ativo
                            @else
                                <svg class="w-2.5 h-2.5 fill-gray-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Inativo
                            @endif
                        </button>
                    </form>

                    <button type="submit" form="configForm" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition text-sm flex justify-center items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Salvar Configurações
                    </button>
                </div>
            </div>

            <!-- Formulário Dinâmico -->
            <form id="configForm" action="/" method="POST" enctype="multipart/form-data" x-data="{ provider: '{{ $configuring->provider ?? 'openai' }}' }">
                @csrf
                @method('PUT')
                <input type="hidden" name="assistant_id" value="{{ $configuring->id }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 space-y-6">
                        <!-- CÉREBRO DA IA -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                                Personalidade e Prompt
                            </h2>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Instruções do Sistema</label>
                            <p class="text-xs text-gray-500 mb-3">Descreva como a IA deve se comportar, qual o tom de voz e as regras de atendimento.</p>
                            <textarea name="system_prompt" rows="12" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ex: Você é um vendedor especializado na loja X. Seja cordial e sempre consulte a base de conhecimento...">{{ $configuring->system_prompt }}</textarea>
                        </div>

                        <!-- BASE DE CONHECIMENTO -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg>
                                Base de Conhecimento
                            </h2>
                            
                            @if($configuring->knowledge_files)
                                <div class="mb-5 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Documentos Anexados</h4>
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        @foreach($configuring->knowledge_files as $file)
                                            <li class="flex items-center gap-2 bg-white px-3 py-2 border border-gray-200 rounded-md shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                {{ $file['name'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <label class="block text-sm font-semibold text-gray-700 mb-1">Anexar Novo Arquivo</label>
                            <p class="text-xs text-gray-500 mb-3">Envie arquivos PDF, Word ou TXT para a IA consultar antes de responder.</p>
                            <input type="file" name="document" accept=".pdf,.doc,.docx,.txt" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- CONEXÃO E MODELOS -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                                Conexão IA
                            </h2>
                            
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Provedor</label>
                            <select name="provider" x-model="provider" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="openai">OpenAI (ChatGPT)</option>
                                <option value="gemini">Google (Gemini)</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="grok">xAI (Grok)</option>
                            </select>

                            <!-- Campos OPENAI -->
                            <div x-show="provider === 'openai'" x-transition>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo da OpenAI</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4 focus:ring-2 focus:ring-indigo-500">
                                    <option value="gpt-4o-mini" {{ $configuring->model == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Rápido/Barato)</option>
                                    <option value="gpt-4o" {{ $configuring->model == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Avançado)</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">OpenAI API Key</label>
                                <input type="password" name="openai_api_key" value="{{ $configuring->openai_api_key }}" placeholder="sk-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Campos GEMINI -->
                            <div x-show="provider === 'gemini'" x-transition style="display: none;">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Google</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4 focus:ring-2 focus:ring-indigo-500">
                                    <option value="gemini-1.5-flash" {{ $configuring->model == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash</option>
                                    <option value="gemini-1.5-pro" {{ $configuring->model == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Gemini API Key</label>
                                <input type="password" name="gemini_api_key" value="{{ $configuring->gemini_api_key }}" placeholder="AIza..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Campos CLAUDE -->
                            <div x-show="provider === 'anthropic'" x-transition style="display: none;">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Claude</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4 focus:ring-2 focus:ring-indigo-500">
                                    <option value="claude-3-haiku" {{ $configuring->model == 'claude-3-haiku' ? 'selected' : '' }}>Claude 3 Haiku</option>
                                    <option value="claude-3.5-sonnet" {{ $configuring->model == 'claude-3.5-sonnet' ? 'selected' : '' }}>Claude 3.5 Sonnet</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Anthropic API Key</label>
                                <input type="password" name="anthropic_api_key" value="{{ $configuring->anthropic_api_key }}" placeholder="sk-ant-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- Campos GROK -->
                            <div x-show="provider === 'grok'" x-transition style="display: none;">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Grok</label>
                                <select name="model" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4 focus:ring-2 focus:ring-indigo-500">
                                    <option value="grok-2-mini" {{ $configuring->model == 'grok-2-mini' ? 'selected' : '' }}>Grok 2 Mini</option>
                                    <option value="grok-2" {{ $configuring->model == 'grok-2' ? 'selected' : '' }}>Grok 2</option>
                                </select>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">xAI API Key</label>
                                <input type="password" name="grok_api_key" value="{{ $configuring->grok_api_key }}" placeholder="xai-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- CARD WHATSAPP -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gray-50/80 backdrop-blur-[1px] z-10 flex flex-col items-center justify-center opacity-0 hover:opacity-100 transition duration-300 rounded-xl">
                                <span class="bg-indigo-600 text-white text-xs font-bold px-3 py-1.5 rounded-full mb-2">Próxima Etapa</span>
                                <p class="text-sm text-gray-700 font-semibold px-4 text-center">Criaremos uma tela dedicada para QR Code e Webhooks!</p>
                            </div>

                            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-500"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                                Canal: WhatsApp
                            </h2>
                            <p class="text-xs text-gray-500 mb-4">A conexão com WhatsApp será feita lendo um QR Code e configurando um Webhook.</p>
                            
                            <div class="space-y-4 opacity-50 pointer-events-none">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nome da Instância</label>
                                    <input type="text" disabled placeholder="ex: suporte-vendas" class="w-full border border-gray-300 rounded-lg p-2 text-sm bg-gray-50">
                                </div>
                                <button disabled class="w-full bg-gray-200 text-gray-500 font-bold py-2 rounded-lg text-sm flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" /></svg>
                                    Gerar QR Code
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </form>

        <!-- ==============================================
             TELA DE LISTAGEM DOS ASSISTENTES
             ============================================== -->
        @else
            <!-- Banner Superior -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mt-6 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <form action="/" method="POST" class="flex items-center gap-2 w-full md:w-auto flex-1 max-w-lg">
                    @csrf
                    <input type="text" name="name" placeholder="Nome do assistente (ex: Vânia - Vendas)" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-5 rounded-lg transition shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Criar
                    </button>
                </form>

                <div class="flex items-center gap-1 bg-gray-100 p-1.5 rounded-lg border border-gray-200 self-end md:self-auto">
                    <button @click="view = 'card'" :class="view === 'card' ? 'bg-white shadow-sm text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 text-xs rounded-md transition flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg> Cards
                    </button>
                    <button @click="view = 'list'" :class="view === 'list' ? 'bg-white shadow-sm text-indigo-600 font-bold' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 text-xs rounded-md transition flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg> Lista
                    </button>
                </div>
            </div>

            <!-- Título da Seção + CONTADOR DINÂMICO (X/Y) -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h2 class="text-base font-bold text-gray-700">
                    Assistentes Cadastrados (<span x-text="currentCount"></span>/<span x-text="total"></span>)
                </h2>
                
                <!-- Combo Box com padrão 'active' -->
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm self-end sm:self-auto">
                    <label class="text-xs text-gray-500 font-medium">Status:</label>
                    <select x-model="filter" class="text-xs font-bold text-gray-700 bg-transparent focus:outline-none cursor-pointer">
                        <option value="active">🟢 Ativos</option>
                        <option value="inactive">⚪ Inativos</option>
                        <option value="all">Todos</option>
                    </select>
                </div>
            </div>
            
            <!-- CARDS -->
            <div x-show="view === 'card'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" style="display: none;" x-transition>
                @forelse($assistants as $assistant)
                    <div x-show="(filter === 'all') || (filter === 'active' && {{ $assistant->is_active ? 'true' : 'false' }}) || (filter === 'inactive' && {{ !$assistant->is_active ? 'true' : 'false' }})"
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:border-indigo-300 hover:shadow-md transition duration-200 flex flex-col justify-between gap-4">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-bold text-gray-800 truncate text-lg">{{ $assistant->name }}</h3>
                            
                            <form action="/" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                <button type="submit" class="text-xs px-3 py-1 rounded-full font-semibold transition border flex items-center gap-1.5 {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                                    @if($assistant->is_active)
                                        <svg class="w-2 h-2 fill-emerald-500" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Ativo
                                    @else
                                        <svg class="w-2 h-2 fill-gray-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Inativo
                                    @endif
                                </button>
                            </form>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-100 pt-5 gap-3 mt-2">
                            <a href="/?configure={{ $assistant->id }}" class="bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-700 text-gray-600 font-bold py-2 px-4 rounded-lg text-sm transition flex-1 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" /></svg> Configurar
                            </a>
                            
                            <form action="/" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este assistente?');">
                                @csrf @method('DELETE')
                                <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                <button type="submit" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2.5 rounded-lg transition text-xs flex items-center justify-center" title="Excluir">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-400 bg-white rounded-xl border border-dashed border-gray-300"><p class="text-sm">Nenhum assistente cadastrado.</p></div>
                @endforelse
            </div>

            <!-- LISTA -->
            <div x-show="view === 'list'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" style="display: none;" x-transition>
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="py-4 px-5 font-semibold">Nome do Assistente</th>
                            <th class="py-4 px-5 font-semibold">Status</th>
                            <th class="py-4 px-5 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($assistants as $assistant)
                            <tr x-show="(filter === 'all') || (filter === 'active' && {{ $assistant->is_active ? 'true' : 'false' }}) || (filter === 'inactive' && {{ !$assistant->is_active ? 'true' : 'false' }})"
                                class="hover:bg-gray-50 transition duration-150 group">
                                <td class="py-4 px-5 font-bold text-gray-800 text-base">{{ $assistant->name }}</td>
                                <td class="py-4 px-5">
                                    <form action="/" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                        <button type="submit" class="text-xs px-3 py-1 rounded-full font-semibold transition border flex items-center gap-1.5 w-max {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200' }}">
                                            @if($assistant->is_active)
                                                <svg class="w-1.5 h-1.5 fill-emerald-500" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Ativo
                                            @else
                                                <svg class="w-1.5 h-1.5 fill-gray-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Inativo
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="py-4 px-5 text-right flex justify-end items-center gap-3 opacity-90 group-hover:opacity-100 transition">
                                    <a href="/?configure={{ $assistant->id }}" class="bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-700 text-gray-600 font-bold py-2 px-4 rounded-lg text-sm transition flex items-center justify-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" /></svg> Configurar
                                    </a>
                                    
                                    <form action="/" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este assistente?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                        <button type="submit" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition text-xs flex items-center justify-center" title="Excluir">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-8 text-gray-400">Nenhum assistente cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</body>
</html>