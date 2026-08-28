<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Configurações do Sistema</h1>

        @if(session('success'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <form action="/?view=settings" method="POST">
            @csrf

            <!-- Fuso Horário -->
            <div class="mb-5">
                <label for="timezone" class="block font-medium text-gray-700 mb-2">Fuso Horário (Timezone)</label>
                <select name="timezone" id="timezone" class="w-full border border-gray-300 rounded-md p-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($timezones as $tz)
                        <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>
                            {{ $tz }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- URL do Webhook -->
            <div class="mb-6">
                <label for="omni_webhook_url" class="block font-medium text-gray-700 mb-2">URL do Webhook Multiagentes</label>
                <input type="url" name="omni_webhook_url" id="omni_webhook_url" value="{{ $webhookUrl }}" placeholder="https://seu-dominio.com/webhook_multiagents.php" class="w-full border border-gray-300 rounded-md p-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-sm text-gray-500 mt-1">Deixe em branco para desativar a integração de webhook.</p>
            </div>

            <button type="submit" class="bg-blue-600 text-white font-medium px-5 py-2 rounded-md hover:bg-blue-700 transition-colors">
                Salvar Configurações
            </button>
        </form>
    </div>
</body>
</html>