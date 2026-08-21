<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioService
{
    /**
     * Transcreve áudio recebido em texto (Speech-to-Text) via OpenAI Whisper
     */
    public function transcribeAudio(string $audioFilePath, string $apiKey, string $provider = 'openai'): ?string
    {
        try {
            if (!file_exists($audioFilePath) || filesize($audioFilePath) < 100) {
                Log::error("Arquivo de áudio inválido ou muito pequeno para transcrição.");
                return null;
            }

            if ($provider === 'openai') {
                $response = Http::withToken($apiKey)
                    ->attach(
                        'file', 
                        file_get_contents($audioFilePath), 
                        'audio.ogg', 
                        ['Content-Type' => 'audio/ogg']
                    )
                    ->post('https://api.openai.com/v1/audio/transcriptions', [
                        'model' => 'whisper-1',
                        'language' => 'pt'
                    ]);

                if ($response->successful()) {
                    return trim($response->json('text') ?? '');
                }

                Log::error("Erro na API Whisper OpenAI: " . $response->body());
            }

            return null;

        } catch (\Throwable $e) {
            Log::error("Exceção na transcrição de áudio: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Converte texto em áudio MP3 (Text-to-Speech) usando a API oficial Google Cloud TTS
     */
    public function textToSpeech(
        string $text, 
        string $googleApiKey, 
        string $voiceName = 'pt-BR-Chirp3-HD-Erinome', 
        string $gender = 'FEMALE', 
        string $langCode = 'pt-BR'
    ): ?string {
        if (empty(trim($text)) || empty($googleApiKey)) {
            return null;
        }

        try {
            $cleanText = str_replace(['*', '#', '_', '`', '~'], '', $text);
            $cleanText = preg_replace("/\n{2,}/", "\n", $cleanText);

            $url = "https://texttospeech.googleapis.com/v1/text:synthesize?key={$googleApiKey}";

            $data = [
                "input" => [
                    "text" => $cleanText
                ],
                "voice" => [
                    "languageCode" => $langCode,
                    "name" => $voiceName,
                    "ssmlGender" => $gender
                ],
                "audioConfig" => [
                    "audioEncoding" => "MP3",
                    "speakingRate" => 1.0,
                    "pitch" => 0
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $data);

            if ($response->failed()) {
                Log::error("Erro na API Google TTS: " . $response->body());
                return null;
            }

            $resJson = $response->json();
            $audioBase64 = $resJson['audioContent'] ?? null;

            if (!$audioBase64) {
                return null;
            }

            $microTime = microtime(true);
            $composeFileName = date('dmyHis', (int)$microTime) . sprintf('%03d', ($microTime - (int)$microTime) * 1000);
            $fileName = "ia_" . $composeFileName . ".mp3";
            $relativePath = "audio_ia/" . $fileName;

            Storage::disk('public')->put($relativePath, base64_decode($audioBase64));

            return Storage::disk('public')->url($relativePath);

        } catch (\Throwable $e) {
            Log::error("Exceção ao gerar voz Google TTS: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Separa URLs da resposta da IA para envio separado no WhatsApp
     */
    public function separateLinksFromText(string $aiResponseText): array
    {
        $links = [];
        
        $textWithoutMarkdownLinks = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', function ($matches) use (&$links) {
            $label = trim($matches[1]);
            $url = trim($matches[2]);
            $links[] = "• {$label}: {$url}";
            return $label;
        }, $aiResponseText);

        $textClean = preg_replace_callback('/https?:\/\/[^\s]+/', function ($matches) use (&$links) {
            $url = trim($matches[0]);
            if (!in_array("• " . $url, $links)) {
                $links[] = "• " . $url;
            }
            return '';
        }, $textWithoutMarkdownLinks);

        $textClean = trim($textClean);

        if (!empty($links)) {
            $textClean .= "\n\nSeguem os links citados em texto logo abaixo:";
        }

        return [
            'audio_text' => $textClean,
            'extracted_links' => !empty($links) ? implode("\n", $links) : null
        ];
    }
}