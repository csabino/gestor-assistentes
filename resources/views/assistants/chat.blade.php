<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - {{ $assistant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">
    <div class="p-4 bg-slate-800 border-b border-slate-700 flex justify-between items-center">
        <h1 class="text-xl font-bold text-indigo-400">Chat com {{ $assistant->name }}</h1>
        <a href="/" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded text-sm transition">Voltar ao Painel</a>
    </div>

    <div id="chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 max-w-4xl mx-auto w-full">
        <div class="bg-slate-800 p-3 rounded-lg max-w-[80%] border border-slate-700">
            <p class="text-slate-300">Olá! Como posso ajudar você hoje?</p>
        </div>
    </div>

    <div class="p-4 bg-slate-800 border-t border-slate-700">
        <form id="chat-form" class="max-w-4xl mx-auto flex gap-2">
            <input type="text" id="chat-input" placeholder="Digite sua mensagem..." required
                class="flex-1 bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-indigo-500 text-white">
            <button type="submit" id="send-btn"
                class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2 rounded-lg font-semibold transition">Enviar</button>
        </form>
    </div>

    <script>
        const assistantId = "{{ $assistant->id }}";
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        let conversationHistory = [];

        function appendMessage(role, text) {
            const wrapper = document.createElement('div');
            wrapper.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';

            const bubble = document.createElement('div');
            bubble.className = role === 'user' 
                ? 'bg-indigo-600 text-white p-3 rounded-lg max-w-[80%]' 
                : 'bg-slate-800 text-slate-200 p-3 rounded-lg max-w-[80%] border border-slate-700';

            bubble.innerText = text;
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
                    throw new Error(`Resposta não é JSON (HTTP ${response.status}): ${rawText.substring(0, 200)}`);
                }

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${data.reply || data.message || JSON.stringify(data)}`);
                }

                const reply = data.reply || 'Sem resposta da IA.';
                appendMessage('assistant', reply);
                
                conversationHistory.push({ role: 'user', content: messageText });
                conversationHistory.push({ role: 'assistant', content: reply });

            } catch (error) {
                appendMessage('assistant', `⚠️ Erro na requisição: ${error.message}`);
            } finally {
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.focus();
            }
        });
    </script>
</body>
</html>