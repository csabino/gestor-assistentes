<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - {{ $assistant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-slate-800 min-h-screen flex flex-col">
    <div class="p-4 bg-white border-b border-gray-200 shadow-sm flex justify-between items-center">
        <h1 class="text-xl font-bold text-indigo-600">Chat com {{ $assistant->name }}</h1>
        <a href="/" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium transition text-gray-700">Voltar ao Painel</a>
    </div>

    <div id="chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 max-w-4xl mx-auto w-full">
        <div class="flex justify-start">
            <div class="bg-white text-slate-700 p-3 rounded-lg max-w-[80%] border border-gray-200 shadow-sm">
                <p>Olá! Como posso ajudar você hoje?</p>
            </div>
        </div>
    </div>

    <div class="p-4 bg-white border-t border-gray-200">
        <form id="chat-form" class="max-w-4xl mx-auto flex gap-2">
            <input type="text" id="chat-input" placeholder="Digite sua mensagem..." required
                class="flex-1 bg-gray-50 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800">
            <button type="submit" id="send-btn"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition">Enviar</button>
        </form>
    </div>

    <script>
        const assistantId = "{{ $assistant->id }}";
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        let conversationHistory = [];

        function parseMarkdownLinks(text) {
            let safeText = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

            const markdownLinkRegex = /\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g;
            safeText = safeText.replace(markdownLinkRegex, function(match, title, url) {
                return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 underline font-semibold break-all">${title}</a>`;
            });

            return safeText.replace(/\n/g, '<br>');
        }

        function appendMessage(role, text) {
            const wrapper = document.createElement('div');
            wrapper.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';

            const bubble = document.createElement('div');
            bubble.className = role === 'user' 
                ? 'bg-indigo-600 text-white p-3 rounded-lg max-w-[80%] shadow-sm' 
                : 'bg-white text-slate-700 p-3 rounded-lg max-w-[80%] border border-gray-200 shadow-sm leading-relaxed';

            if (role === 'assistant') {
                bubble.innerHTML = parseMarkdownLinks(text);
            } else {
                bubble.innerText = text;
            }

            wrapper.appendChild(bubble);
            chatMessages.appendChild(wrapper);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const messageText = chatInput.value.trim();
            if (!messageText) return;

            appendMessage('user', messageText);
            chatInput.value = '';
            chatInput.disabled = true;
            sendBtn.disabled = true;

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'chat',
                        assistant_id: assistantId,
                        message: messageText,
                        history: conversationHistory
                    })
                });

                const rawText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (e) {
                    throw new Error(`Resposta inválida do servidor (HTTP ${response.status})`);
                }

                if (!response.ok) {
                    throw new Error(data.reply || data.message || `Erro HTTP ${response.status}`);
                }

                const reply = data.reply || 'Sem resposta da IA.';
                appendMessage('assistant', reply);
                
                conversationHistory.push({ role: 'user', content: messageText });
                conversationHistory.push({ role: 'assistant', content: reply });

            } catch (error) {
                appendMessage('assistant', `⚠️ Erro: ${error.message}`);
            } finally {
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.focus();
            }
        });
    </script>
</body>
</html>