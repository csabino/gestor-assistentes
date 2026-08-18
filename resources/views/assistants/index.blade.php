<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Assistentes AI</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234F46E5' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>

    <script>
        function openChatPopup(id) {
            const width = 720;
            const height = 480;
            const left = (window.top.outerWidth / 2) + window.top.screenX - (width / 2);
            const top = (window.top.outerHeight / 2) + window.top.screenY - (height / 2);
            
            window.open(
                '/?chat_id=' + id,
                'chat_' + id,
                `toolbar=no, location=no, status=no, menubar=no, scrollbars=no, resizable=yes, width=${width}, height=${height}, top=${top}, left=${left}`
            );
        }

        function copyChatLink(id) {
            const url = window.location.origin + '/?chat_id=' + id;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link do chat copiado para a área de transferência!');
            }).catch(() => {
                alert('Não foi possível copiar o link.');
            });
        }
    </script>

    @if($configuring)
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const key = 'scrollpos_config_{{ $configuring->id }}';
                const scrollpos = sessionStorage.getItem(key);
                if (scrollpos) {
                    window.scrollTo(0, parseInt(scrollpos));
                    sessionStorage.removeItem(key);
                }
            });
            window.addEventListener("beforeunload", function() {
                const key = 'scrollpos_config_{{ $configuring->id }}';
                sessionStorage.setItem(key, window.scrollY);
            });
        </script>
    @endif
</head>
<body class="bg-gray-50 font-sans text-gray-900" 
    x-data="{ 
        view: localStorage.getItem('assistant_view') || 'card', 
        filter: localStorage.getItem('assistant_filter') || 'active',
        total: {{ $assistants->count() }},
        activeCount: {{ $assistants->where('is_active', true)->count() }},
        inactiveCount: {{ $assistants->where('is_active', false)->count() }},
        get currentCount() {
            if (this.filter === 'active') return this.activeCount;
            if (this.filter === 'inactive') return this.inactiveCount;
            return this.total;
        }
    }"
    x-init="
        $watch('filter', value => localStorage.setItem('assistant_filter', value));
        $watch('view', value => localStorage.setItem('assistant_view', value));
    "
>
    
    <nav class="bg-indigo-600 text-white shadow-sm relative z-50">
        <div class="container mx-auto px-4 flex justify-between items-center h-14">
            <div class="flex items-center gap-6 h-full">
                <a href="/" class="font-bold text-lg flex items-center gap-2.5 hover:text-indigo-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" />
                    </svg>
                    Painel
                </a>
                
                <div class="h-full flex">
                    <a href="/" class="flex items-center px-3 text-sm font-medium border-b-2 border-white text-white">Robôs IA</a>
                    <a href="/?view=equipe" class="flex items-center px-3 text-sm font-medium border-b-2 border-transparent text-indigo-100 hover:text-white hover:border-indigo-300 transition">Equipe & Agendas</a>
                    <a href="/?view=agenda" class="flex items-center px-3 text-sm font-medium border-b-2 border-transparent text-indigo-100 hover:text-white hover:border-indigo-300 transition">Calendário</a>
                </div>
            </div>
            <span class="text-xs bg-indigo-500 px-3 py-1 rounded-full font-medium border border-indigo-400">Multi-Model</span>
        </div>
    </nav>

    <div class="container mx-auto px-4 max-w-6xl mb-12">
        
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg mt-6 text-sm flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-emerald-600"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mt-6 text-sm flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-600"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                {{ session('error') }}
            </div>
        @endif

        @if($configuring)
            
            <div class="sticky top-0 z-40 bg-gray-50/90 backdrop-blur-md py-4 mb-6 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 -mx-4 px-4 sm:mx-0 sm:px-0 shadow-sm mt-4">
                <div class="flex items-center gap-4">
                    <a href="/" onclick="sessionStorage.removeItem('scrollpos_config_{{ $configuring->id }}'); window.onbeforeunload = null;" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1.5 text-sm transition bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg> Voltar
                    </a>
                    <div class="h-6 w-px bg-gray-300 hidden md:block"></div>
                    <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">{{ $configuring->name }}</h1>
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg> Salvar Configurações
                    </button>
                </div>
            </div>

            <form id="configForm" action="/" method="POST" enctype="multipart/form-data" 
                x-data="{ 
                    provider: '{{ $configuring->provider ?? 'openai' }}',
                    wa_provider: '{{ $configuring->whatsapp_provider ?? '' }}',
                    wa_url: '{{ $configuring->whatsapp_url ?? '' }}',
                    wa_instance: '{{ $configuring->whatsapp_instance ?? '' }}',
                    wa_token: '{{ $configuring->whatsapp_token ?? '' }}',
                    
                    testing: false,
                    testResult: null,
                    showWaModal: false,
                    showWebhookModal: false,
                    waLoading: false,
                    waResult: null,
                    pollAttempts: 0,
                    waStatus: 'checking', 
                    
                    getApiKey() {
                        if (this.provider === 'openai') return document.querySelector('input[name=\'openai_api_key\']').value;
                        if (this.provider === 'gemini') return document.querySelector('input[name=\'gemini_api_key\']').value;
                        if (this.provider === 'anthropic') return document.querySelector('input[name=\'anthropic_api_key\']').value;
                        if (this.provider === 'grok') return document.querySelector('input[name=\'grok_api_key\']').value;
                        return '';
                    },
                    
                    getWaParams() {
                        return { provider: this.wa_provider, url: this.wa_url, instance: this.wa_instance, token: this.wa_token };
                    },

                    async testConnection() {
                        this.testing = true;
                        this.testResult = null;
                        try {
                            const response = await fetch('/', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ action: 'test_ai', provider: this.provider, api_key: this.getApiKey() })
                            });
                            this.testResult = await response.json();
                        } catch (e) {
                            this.testResult = { success: false, message: 'Erro ao processar requisição.' };
                        } finally {
                            this.testing = false;
                        }
                    },

                    async checkWaStatusSilent() {
                        if(!this.wa_provider || !this.wa_url || !this.wa_token) {
                            this.waStatus = 'disconnected';
                            return;
                        }
                        this.waStatus = 'checking';
                        try {
                            const response = await fetch('/', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ action: 'status_whatsapp', ...this.getWaParams() })
                            });
                            const data = await response.json();
                            this.waStatus = data.connected ? 'connected' : 'disconnected';
                        } catch(e) {
                            this.waStatus = 'disconnected';
                        }
                    },

                    async disconnectWa() {
                        if(!confirm('Tem certeza que deseja desconectar o celular deste painel?')) return;
                        this.waStatus = 'checking';
                        try {
                            const response = await fetch('/', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ action: 'disconnect_whatsapp', ...this.getWaParams() })
                            });
                            const data = await response.json();
                            
                            if (data.success) {
                                this.waStatus = 'disconnected';
                                window.location.reload(); 
                            } else {
                                alert('A API não conseguiu desconectar:\n\n' + (data.message || 'Erro desconhecido.'));
                                this.checkWaStatusSilent(); 
                            }
                        } catch(e) {
                            alert('Erro na requisição.');
                            this.checkWaStatusSilent();
                        }
                    },

                    async startWaConnection() {
                        this.showWaModal = true;
                        this.pollAttempts = 0;
                        this.waResult = null;
                        await this.runWaPoll();
                    },

                    async runWaPoll() {
                        if (!this.showWaModal) return;
                        if (!this.waResult || (!this.waResult.qr && !this.waResult.connected)) this.waLoading = true;
                        
                        try {
                            const response = await fetch('/', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: JSON.stringify({ action: 'test_whatsapp', ...this.getWaParams() })
                            });
                            const data = await response.json();
                            this.waResult = data;
                            
                            if (data.connected) {
                                this.waStatus = 'connected';
                                setTimeout(() => {
                                    this.showWaModal = false;
                                    window.location.reload();
                                }, 1500);
                            } else if (data.success && this.pollAttempts < 20 && this.showWaModal) {
                                this.pollAttempts++;
                                setTimeout(() => {
                                    if (this.showWaModal) this.runWaPoll();
                                }, 3000);
                            }
                        } catch (e) {
                        } finally {
                            this.waLoading = false;
                        }
                    }
                }"
                x-init="checkWaStatusSilent()">
                
                @csrf @method('PUT')
                <input type="hidden" name="assistant_id" value="{{ $configuring->id }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-full">
                        <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                            Personalidade e Prompt
                        </h2>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Instruções do Sistema</label>
                        <textarea name="system_prompt" rows="12" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ex: Você é um vendedor especializado na loja X...">{{ $configuring->system_prompt }}</textarea>
                    </div>

                    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-full">
                        <div class="border-b border-gray-100 pb-3 mb-4 flex items-center justify-between gap-2">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                                Conexão IA
                            </h2>
                            <button type="button" x-on:click="testConnection()" :disabled="testing" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold py-1.5 px-3 rounded-lg border border-indigo-200 flex items-center shrink-0">
                                <span x-text="testing ? '⌛ Testando...' : '⚡ Testar'"></span>
                            </button>
                        </div>

                        <div x-show="testResult !== null" x-transition class="mb-4 p-3 rounded-lg text-xs border" :class="testResult?.success ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'">
                            <p class="font-bold mb-0.5" x-text="testResult?.success ? '✅ Sucesso!' : '❌ Falha'"></p>
                            <p x-text="testResult?.message"></p>
                        </div>

                        <label class="block text-sm font-semibold text-gray-700 mb-1">Provedor</label>
                        <select name="provider" x-model="provider" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-5 focus:ring-2 focus:ring-indigo-500">
                            <option value="openai">OpenAI (ChatGPT)</option>
                            <option value="gemini">Google (Gemini)</option>
                            <option value="anthropic">Anthropic (Claude)</option>
                            <option value="grok">xAI (Grok)</option>
                        </select>

                        <div x-show="provider === 'openai'" x-transition>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo da OpenAI</label>
                            <select name="model" :disabled="provider !== 'openai'" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                <option value="gpt-4o-mini" {{ $configuring->model == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Rápido/Barato)</option>
                                <option value="gpt-4o" {{ $configuring->model == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Avançado)</option>
                            </select>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">OpenAI API Key</label>
                            <input type="password" name="openai_api_key" value="{{ $configuring->openai_api_key }}" placeholder="sk-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div x-show="provider === 'gemini'" x-transition style="display: none;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Google</label>
                            <select name="model" :disabled="provider !== 'gemini'" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                <option value="gemini-1.5-flash" {{ $configuring->model == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash</option>
                                <option value="gemini-1.5-pro" {{ $configuring->model == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro</option>
                            </select>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Gemini API Key</label>
                            <input type="password" name="gemini_api_key" value="{{ $configuring->gemini_api_key }}" placeholder="AIza..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div x-show="provider === 'anthropic'" x-transition style="display: none;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Claude</label>
                            <select name="model" :disabled="provider !== 'anthropic'" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                <option value="claude-3-haiku" {{ $configuring->model == 'claude-3-haiku' ? 'selected' : '' }}>Claude 3 Haiku</option>
                                <option value="claude-3.5-sonnet" {{ $configuring->model == 'claude-3.5-sonnet' ? 'selected' : '' }}>Claude 3.5 Sonnet</option>
                            </select>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Anthropic API Key</label>
                            <input type="password" name="anthropic_api_key" value="{{ $configuring->anthropic_api_key }}" placeholder="sk-ant-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div x-show="provider === 'grok'" x-transition style="display: none;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Modelo do Grok</label>
                            <select name="model" :disabled="provider !== 'grok'" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm mb-4">
                                <option value="grok-2-mini" {{ $configuring->model == 'grok-2-mini' ? 'selected' : '' }}>Grok 2 Mini</option>
                                <option value="grok-2" {{ $configuring->model == 'grok-2' ? 'selected' : '' }}>Grok 2</option>
                            </select>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">xAI API Key</label>
                            <input type="password" name="grok_api_key" value="{{ $configuring->grok_api_key }}" placeholder="xai-..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-200 h-full flex flex-col">
                        <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" /></svg>
                            Base de Conhecimento
                        </h2>
                        
                        <div class="flex-1">
                            @if($configuring->knowledge_files && count($configuring->knowledge_files) > 0)
                                <div class="mb-5 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Documentos Anexados ({{ count($configuring->knowledge_files) }})</h4>
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        @foreach($configuring->knowledge_files as $index => $file)
                                            <li class="flex items-center justify-between bg-white px-3 py-2 border border-gray-200 rounded-md shadow-sm">
                                                <div class="flex items-center gap-2 truncate">
                                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    <span class="truncate font-medium">{{ $file['name'] }}</span>
                                                </div>
                                                <button type="button" onclick="if(confirm('Remover arquivo?')) document.getElementById('deleteFileForm_{{ $index }}').submit();" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1 rounded transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="mt-auto border-t border-gray-100 pt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Anexar Arquivos (PDF, Word, TXT)</label>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                <input type="file" name="documents[]" multiple accept=".pdf,.doc,.docx,.txt" class="block w-full text-sm text-gray-500 border border-gray-200 rounded-lg p-1">
                                <button type="submit" form="configForm" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-1.5 shrink-0 transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Anexar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white p-4 rounded-xl shadow-sm border border-gray-200 h-full flex flex-col">
                        
                        <h2 class="text-base font-bold text-gray-800 border-b border-gray-100 pb-2 mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-500"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                                Canal: WhatsApp
                                <button type="button" x-show="wa_provider !== ''" x-cloak x-on:click="showWebhookModal = true" class="text-slate-500 hover:text-indigo-600 bg-gray-100 hover:bg-indigo-50 p-1 rounded-md transition text-xs flex items-center gap-1 border border-gray-200 ml-1" title="Diagnóstico e Webhook">
                                    🛠️ <span class="text-[10px] font-semibold text-gray-600">Webhook</span>
                                </button>
                            </div>
                            
                            <div class="flex items-center gap-1.5" x-show="wa_provider !== ''" x-cloak>
                                <span x-show="waStatus === 'checking'" class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 animate-pulse border border-gray-200">Verificando...</span>
                                <span x-show="waStatus === 'connected'" class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 flex items-center gap-1 border border-emerald-200 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Conectado
                                </span>
                                <span x-show="waStatus === 'disconnected'" class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 flex items-center gap-1 border border-red-200 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Desconectado
                                </span>
                                
                                <button type="button" title="Desconectar" x-show="waStatus === 'connected'" x-on:click="disconnectWa()" class="w-6 h-6 rounded-full bg-red-50 hover:bg-red-100 text-red-600 transition border border-red-200 flex items-center justify-center cursor-pointer shadow-sm shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" /></svg>
                                </button>
                            </div>
                        </h2>

                        <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Plataforma</label>
                        <select name="whatsapp_provider" x-model="wa_provider" x-on:change="checkWaStatusSilent()" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-3 focus:ring-2 focus:ring-indigo-500">
                            <option value="">Desativado</option>
                            <option value="uazapi">UaZapi</option>
                            <option value="evolution">Evolution API</option>
                            <option value="meta">API Oficial (Meta)</option>
                            <option value="zapi">Z-API</option>
                            <option value="chatpro">ChatPro</option>
                        </select>

                        <div x-show="wa_provider !== ''" x-transition class="space-y-3 flex-1">
                            <template x-if="wa_provider === 'uazapi'">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">URL (UaZapi)</label>
                                    <input type="url" name="whatsapp_url" x-model="wa_url" x-on:change="checkWaStatusSilent()" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2" placeholder="https://api.uazapi.dev">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Nome da Instância</label>
                                    <input type="text" name="whatsapp_instance" x-model="wa_instance" x-on:change="checkWaStatusSilent()" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2" placeholder="Ex: suporte">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Instance Token</label>
                                    <input type="password" name="whatsapp_token" x-model="wa_token" x-on:change="checkWaStatusSilent()" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px]" placeholder="Ex: T0K3N...">
                                </div>
                            </template>
                            <template x-if="wa_provider === 'evolution'">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">URL (Evolution)</label>
                                    <input type="url" name="whatsapp_url" x-model="wa_url" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2" placeholder="https://api...">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Nome da Instância</label>
                                    <input type="text" name="whatsapp_instance" x-model="wa_instance" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Global API Key</label>
                                    <input type="password" name="whatsapp_token" x-model="wa_token" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px]">
                                </div>
                            </template>
                            <template x-if="wa_provider === 'meta'">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Phone Number ID</label>
                                    <input type="text" name="whatsapp_instance" x-model="wa_instance" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Access Token</label>
                                    <input type="password" name="whatsapp_token" x-model="wa_token" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Verify Token</label>
                                    <input type="text" name="whatsapp_verify_token" value="{{ $configuring->whatsapp_verify_token }}" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px]">
                                </div>
                            </template>
                            <template x-if="wa_provider === 'zapi'">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">ID da Instância</label>
                                    <input type="text" name="whatsapp_instance" x-model="wa_instance" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Token da Instância</label>
                                    <input type="password" name="whatsapp_token" x-model="wa_token" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Client-Token</label>
                                    <input type="text" name="whatsapp_verify_token" value="{{ $configuring->whatsapp_verify_token }}" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px]">
                                </div>
                            </template>
                            <template x-if="wa_provider === 'chatpro'">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Endpoint URL</label>
                                    <input type="url" name="whatsapp_url" x-model="wa_url" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px] mb-2">
                                    <label class="block text-[11px] font-semibold text-gray-700 mb-0.5">Token</label>
                                    <input type="password" name="whatsapp_token" x-model="wa_token" class="w-full border border-gray-300 rounded-md p-1.5 text-[11px]">
                                </div>
                            </template>
                        </div>

                        <div x-show="wa_provider !== ''" x-transition class="mt-auto border-t border-gray-100 pt-3 mt-4 space-y-3">
                            <button type="button" x-on:click="startWaConnection()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c0 .621.504 1.125 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c0 .621.504 1.125 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c0 .621.504 1.125 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" /></svg>
                                Conectar / QR Code
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MODAL DO WEBHOOK E DIAGNÓSTICO -->
                <div x-show="showWebhookModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4" x-transition>
                    <div x-on:click.away="showWebhookModal = false" class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 relative border border-gray-100 space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                🛠️ Webhook de Retorno & Diagnóstico
                            </h3>
                            <button type="button" x-on:click="showWebhookModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- URL DO WEBHOOK -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">URL do Webhook (Cole no seu Provedor de WhatsApp)</label>
                            <div class="flex items-center gap-2">
                                <input type="text" readonly id="modalWebhookUrl" value="{{ request()->schemeAndHttpHost() }}/webhook/whatsapp/{{ $configuring->id }}" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs text-gray-700 font-mono outline-none shadow-inner">
                                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('modalWebhookUrl').value); alert('URL do Webhook copiada!');" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-xs transition shrink-0 shadow-sm">Copiar</button>
                            </div>
                        </div>

                        <!-- RAIO-X DO WEBHOOK -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-gray-700">🔍 Diagnóstico do Webhook</label>
                                <button type="button" onclick="window.location.reload();" class="text-indigo-600 hover:underline text-xs">Atualizar</button>
                            </div>
                            <div class="bg-gray-900 text-gray-100 p-3 rounded-lg text-[11px] font-mono shadow-inner border border-gray-800 max-h-72 overflow-y-auto">
                                @if($lastWebhook)
                                    <div class="space-y-1">
                                        <p><span class="text-gray-500">Horário:</span> <span class="text-gray-300">{{ $lastWebhook['timestamp'] ?? '-' }}</span></p>
                                        <p><span class="text-gray-500">De (Número):</span> <span class="text-amber-400 font-bold">{{ $lastWebhook['sender'] ?? '-' }}</span></p>
                                        <p><span class="text-gray-500">Mensagem:</span> <span class="text-white">"{{ $lastWebhook['user_message'] ?? '-' }}"</span></p>
                                        
                                        @if(isset($lastWebhook['ai_reply']))
                                            <p class="truncate"><span class="text-gray-500">Resposta IA:</span> <span class="text-emerald-400">{{ $lastWebhook['ai_reply'] }}</span></p>
                                        @endif

                                        <p class="pt-1 border-t border-gray-800 mt-1">
                                            <span class="text-gray-500">Envio WhatsApp:</span>
                                            @if(($lastWebhook['wa_send_result']['success'] ?? false) === true)
                                                <span class="text-emerald-400 font-bold">✅ ENTREGUE</span>
                                            @else
                                                <span class="text-red-400 font-bold">❌ FALHOU</span>
                                            @endif
                                        </p>

                                        @if(isset($lastWebhook['error']))
                                            <p class="text-red-400 bg-red-950/50 p-1 rounded mt-1 border border-red-900/50 break-words">
                                                Motivo: {{ $lastWebhook['error'] }}
                                            </p>
                                        @endif

                                        @if(isset($lastWebhook['wa_send_result']['error']))
                                            <p class="text-red-400 bg-red-950/50 p-1 rounded mt-1 border border-red-900/50 break-words">
                                                WhatsApp API: {{ $lastWebhook['wa_send_result']['error'] }}
                                            </p>
                                        @endif

                                        @if(isset($lastWebhook['raw_snippet']))
                                            <div class="mt-2 pt-2 border-t border-gray-800 text-[10px] text-gray-400">
                                                <p class="font-bold text-gray-300 mb-1">Payload JSON Recebido:</p>
                                                <p class="bg-black/60 p-1.5 rounded font-mono break-words text-gray-300 text-[9px]">{{ $lastWebhook['raw_snippet'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-gray-500 italic py-2 text-center">⏳ Nenhum webhook recebido ainda. Envie um "Oi" no WhatsApp para testar.</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="button" x-on:click="showWebhookModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-5 py-2 rounded-lg text-xs transition">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MODAL DO QR CODE -->
                <div x-show="showWaModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4" x-transition>
                    <div x-on:click.away="showWaModal = false" class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center relative border border-gray-100">
                        
                        <button type="button" x-on:click="showWaModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>

                        <h3 class="text-lg font-bold text-gray-800 mb-1 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                            Conexão WhatsApp
                        </h3>
                        <p class="text-xs text-gray-500 mb-6">{{ $configuring->name }}</p>

                        <div x-show="waLoading && !waResult" class="py-8 space-y-3">
                            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-500 border-t-transparent"></div>
                            <p class="text-sm font-semibold text-gray-600">Acessando API...</p>
                        </div>

                        <div x-show="waResult !== null">
                            <template x-if="waResult?.connected">
                                <div class="py-6 space-y-3">
                                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    </div>
                                    <h4 class="text-base font-bold text-gray-800">WhatsApp Conectado!</h4>
                                    <p class="text-xs text-gray-500" x-text="waResult?.message"></p>
                                </div>
                            </template>

                            <template x-if="waResult?.qr && !waResult?.connected">
                                <div class="space-y-4">
                                    <p class="text-xs text-gray-600 font-medium" x-text="waResult?.message"></p>
                                    <div class="bg-gray-50 p-4 rounded-xl inline-block border border-gray-200 shadow-inner">
                                        <img :src="waResult.qr" alt="QR Code" class="w-56 h-56 object-contain mx-auto rounded-lg">
                                    </div>
                                    <p class="text-[11px] text-gray-400">1. Abra o WhatsApp no celular<br>2. Toque em <b>Aparelhos Conectados</b> > <b>Conectar um Aparelho</b></p>
                                    
                                    <div class="text-[10px] text-gray-400 mt-2 flex items-center justify-center gap-1.5 bg-gray-50 py-1.5 rounded border border-gray-100">
                                        <span class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-emerald-500 border-t-transparent"></span>
                                        Aguardando leitura... (Tentativa <span x-text="pollAttempts"></span>/20)
                                    </div>
                                </div>
                            </template>

                            <template x-if="!waResult?.qr && !waResult?.connected && waResult?.success">
                                <div class="py-6 space-y-3">
                                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent"></div>
                                    <p class="text-xs text-gray-600 font-semibold">Instância acordou! Obtendo imagem do QR Code...</p>
                                    <p class="text-[11px] text-gray-400" x-text="`Tentativa ${pollAttempts} de 20 (Aguarde 3s...)`"></p>
                                </div>
                            </template>

                            <template x-if="!waResult?.success">
                                <div class="py-4 space-y-2">
                                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                    </div>
                                    <p class="text-xs font-semibold text-red-600" x-text="waResult?.message"></p>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 flex gap-2">
                            <button type="button" x-on:click="runWaPoll()" :disabled="waLoading" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg text-xs transition">
                                Atualizar / Tentar Novamente
                            </button>
                            <button type="button" x-on:click="showWaModal = false" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-lg text-xs transition">
                                Fechar
                            </button>
                        </div>

                    </div>
                </div>

            </form>

            @if($configuring->knowledge_files && count($configuring->knowledge_files) > 0)
                @foreach($configuring->knowledge_files as $index => $file)
                    <form id="deleteFileForm_{{ $index }}" action="/" method="POST" class="hidden">
                        @csrf @method('DELETE')
                        <input type="hidden" name="assistant_id" value="{{ $configuring->id }}">
                        <input type="hidden" name="file_index" value="{{ $index }}">
                    </form>
                @endforeach
            @endif

        @else
            <!-- TELA DE LISTAGEM DE ASSISTENTES -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mt-6 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <form action="/" method="POST" class="flex items-center gap-2 w-full md:w-auto flex-1 max-w-lg">
                    @csrf
                    <input type="text" name="name" placeholder="Nome do assistente (ex: Vânia - Vendas)" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-5 rounded-lg transition shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Criar
                    </button>
                </form>
            </div>
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h2 class="text-base font-bold text-gray-700">Assistentes Cadastrados (<span x-text="currentCount"></span>/<span x-text="total"></span>)</h2>
                
                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-gray-200/80 p-0.5 rounded-lg border border-gray-200">
                        <button type="button" @click="view = 'card'" :class="view === 'card' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                            Cards
                        </button>
                        <button type="button" @click="view = 'list'" :class="view === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M3.75 4.5h16.5" /></svg>
                            Lista
                        </button>
                    </div>

                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                        <label class="text-xs text-gray-500 font-medium">Status:</label>
                        <select x-model="filter" class="text-xs font-bold text-gray-700 bg-transparent focus:outline-none cursor-pointer">
                            <option value="active">🟢 Ativos</option>
                            <option value="inactive">⚪ Inativos</option>
                            <option value="all">Todos</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- CARDS GRID -->
            <div x-show="view === 'card'" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($assistants as $assistant)
                    <div x-show="(filter === 'all') || (filter === 'active' && {{ $assistant->is_active ? 'true' : 'false' }}) || (filter === 'inactive' && {{ !$assistant->is_active ? 'true' : 'false' }})"
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:border-indigo-300 hover:shadow-md transition duration-200 flex flex-col justify-between gap-4">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="flex items-center gap-1.5 truncate">
                                <h3 class="font-bold text-gray-800 truncate text-lg">{{ $assistant->name }}</h3>
                                
                                <button type="button" title="Testar Chat Público" onclick="openChatPopup({{ $assistant->id }})" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-1.5 rounded-full transition shadow-sm border border-indigo-100 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.522 1.522 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                                </button>

                                <button type="button" title="Copiar link do chat" onclick="copyChatLink({{ $assistant->id }})" class="text-gray-400 hover:text-indigo-600 p-1.5 rounded-full transition hover:bg-gray-100 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 011.927-.184" /></svg>
                                </button>
                            </div>

                            <form action="/" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                <button type="submit" class="text-xs px-3 py-1 rounded-full font-semibold transition border flex items-center gap-1.5 shrink-0 {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-300' }}">
                                    @if($assistant->is_active) <svg class="w-2 h-2 fill-emerald-500" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Ativo @else <svg class="w-2 h-2 fill-gray-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Inativo @endif
                                </button>
                            </form>
                        </div>
                        
                        <div class="flex items-center justify-between border-t border-gray-100 pt-5 gap-2 mt-2">
                            <a href="/?configure={{ $assistant->id }}" onclick="sessionStorage.removeItem('scrollpos_config_{{ $assistant->id }}');" class="bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-700 text-gray-600 font-bold py-2 px-2 rounded-lg text-xs transition flex-1 flex items-center justify-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                                Configurar
                            </a>
                            
                            <a href="/?view=agenda&assistant_id={{ $assistant->id }}" class="bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-700 text-gray-600 font-bold py-2 px-2 rounded-lg text-xs transition flex-1 flex items-center justify-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008z" /></svg>
                                Agenda
                            </a>

                            <form action="/" method="POST" onsubmit="return confirm('Tem certeza?');">
                                @csrf @method('DELETE')
                                <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                <button type="submit" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition text-xs flex items-center justify-center" title="Excluir">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-400">Nenhum assistente cadastrado.</div>
                @endforelse
            </div>
            
            <!-- LISTA TABULAR -->
            <div x-show="view === 'list'" x-transition class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
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
                                
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-gray-800 text-base">{{ $assistant->name }}</span>
                                        
                                        <button type="button" title="Testar Chat Público" onclick="openChatPopup({{ $assistant->id }})" class="text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-1.5 rounded-full transition shadow-sm border border-indigo-100 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.522 1.522 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                                        </button>

                                        <button type="button" title="Copiar link do chat" onclick="copyChatLink({{ $assistant->id }})" class="text-gray-400 hover:text-indigo-600 p-1.5 rounded-full transition hover:bg-gray-100 shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 011.927-.184" /></svg>
                                        </button>
                                    </div>
                                </td>

                                <td class="py-4 px-5">
                                    <form action="/" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                        <button type="submit" class="text-xs px-3 py-1 rounded-full font-semibold transition border flex items-center gap-1.5 w-max {{ $assistant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-300' }}">
                                            @if($assistant->is_active) <svg class="w-1.5 h-1.5 fill-emerald-500" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Ativo @else <svg class="w-1.5 h-1.5 fill-gray-400" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg> Inativo @endif
                                        </button>
                                    </form>
                                </td>

                                <td class="py-4 px-5 text-right flex justify-end items-center gap-2 opacity-90 group-hover:opacity-100 transition">
                                    <a href="/?configure={{ $assistant->id }}" onclick="sessionStorage.removeItem('scrollpos_config_{{ $assistant->id }}');" class="bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-700 text-gray-600 font-bold py-1.5 px-3 rounded-lg text-xs transition flex items-center justify-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg> Configurar
                                    </a>

                                    <a href="/?view=agenda&assistant_id={{ $assistant->id }}" class="bg-white border border-gray-200 hover:border-indigo-300 hover:text-indigo-700 text-gray-600 font-bold py-1.5 px-3 rounded-lg text-xs transition flex items-center justify-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008z" /></svg> Agenda
                                    </a>
                                    
                                    <form action="/" method="POST" onsubmit="return confirm('Tem certeza?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="assistant_id" value="{{ $assistant->id }}">
                                        <button type="submit" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded-md transition text-xs flex items-center justify-center" title="Excluir">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
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