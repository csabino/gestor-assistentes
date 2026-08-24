<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OmniTicketService
{
    /**
     * Envia os dados de conversação para o Webhook remoto do InSoft Omni.
     *
     * @param array $payload Dados da mensagem (conversation, pushName, type, remoteJidAlt, etc.)
     * @return array
     */
    public function sendToOmni(array $payload): array
    {
        $webhookUrl = env('OMNI_WEBHOOK_URL');

        if (empty($webhookUrl)) {
            return [
                'success' => false,
                'message' => 'URL do Webhook OMNI_WEBHOOK_URL não configurada no .env'
            ];
        }

        try {
            // Dispara o HTTP POST para o servidor onde o PHP do Omni/osTicket está rodando
            $response = Http::timeout(10)
                ->acceptJson()
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json() ?? $response->body()
                ];
            }

            Log::warning('Omni Webhook retornou erro HTTP', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'status'  => $response->status(),
                'message' => 'Falha ao processar no servidor Omni.'
            ];

        } catch (\Throwable $e) {
            Log::error('Exceção ao conectar no Webhook Omni: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro de conexão com o servidor Omni: ' . $e->getMessage()
            ];
        }
    }
}