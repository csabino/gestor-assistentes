<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com {{ $assistant->name }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234F46E5' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-100 flex flex-col h-screen font-sans" 
    x-data="{
        newMessage: '',
        isTyping: false,
        assistantName: @json($assistant->name),
        providerName: @json(ucfirst($assistant->provider ?? 'IA')),
        messages: [
            { id: 1, role: 'assistant', content: 'Olá! Sou ' + @json($assistant->name) + '. Como posso te ajudar hoje?' }
        ],
        sendMessage() {
            if(this.newMessage.trim() === '') return;
            
            this.messages.push({ id: Date.now(), role: 'user', content: this.newMessage });
            this.newMessage = '';
            this.scrollToBottom();
            
            this.isTyping = true;
            
            setTimeout(() => {
                this.isTyping = false;
                this.messages.push({ 
                    id: Date.now(), 
                    role: 'assistant', 
                    content: 'Integração com o modelo ' + this.providerName + ' pronta para ser conectada!' 
                });
                this.scrollToBottom();
            }, 1200);
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatBox;
                if(container) container.scrollTop = container.scrollHeight;
            });
        }
    }">

    <header class="bg-white shadow-sm px-4 py-3 flex items-center justify-between shrink-0 z-10">
        <div class="flex items-center gap-3">
            <div class="relative">
                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-lg border border-indigo-200">
                    {{ strtoupper(substr($assistant->name, 0, 1)) }}
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
            </div>
            <div>
                <h1 class="font-bold text-gray-800 text-sm leading-tight">{{ $assistant->name }}</h1>
                <p class="text-xs text-green-500 font-medium leading-tight">Online</p>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50" x-ref="chatBox">
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                <div class="max-w-[85%] px-4 py-2.5 rounded-2xl text-sm shadow-sm"
                     :class="msg.role === 'user' ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none'">
                    <span x-text="msg.content"></span>
                </div>
                <span class="text-[10px] text-gray-400 mt-1 px-1" x-text="msg.role === 'user' ? 'Você' : assistantName"></span>
            </div>
        </template>

        <div x-show="isTyping" x-transition class="flex items-start">
            <div class="bg-white border border-gray-200 px-4 py-3 rounded-2xl rounded-bl-none shadow-sm flex gap-1">
                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </main>

    <footer class="bg-white p-3 border-t border-gray-200 shrink-0">
        <form @submit.prevent="sendMessage" class="flex items-center gap-2">
            <input type="text" x-model="newMessage" placeholder="Digite sua mensagem..." 
                class="flex-1 bg-gray-100 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-full px-4 py-2.5 text-sm transition outline-none">
            
            <button type="submit" :disabled="newMessage.trim() === ''" class="w-10 h-10 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white rounded-full flex items-center justify-center transition shadow-sm shrink-0">
                <svg class="w-4 h-4 translate-x-[-1px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
            </button>
        </form>
        <div class="text-center mt-2">
            <p class="text-[9px] text-gray-400 font-medium">Powered by Gestor de Assistentes AI</p>
        </div>
    </footer>

</body>
</html>