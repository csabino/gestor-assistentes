<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarService
{
    private function getAccessToken(int $assistantId): ?string
    {
        $clientId = Setting::where('assistant_id', $assistantId)->where('key', 'google_client_id')->value('value');
        $clientSecret = Setting::where('assistant_id', $assistantId)->where('key', 'google_client_secret')->value('value');
        $refreshToken = Setting::where('assistant_id', $assistantId)->where('key', 'google_refresh_token')->value('value');

        if (!$clientId || !$clientSecret || !$refreshToken) {
            Log::error("Credenciais do Google Calendar incompletas para o assistente #{$assistantId}");
            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error("Erro na renovação do Token Google para assistente #{$assistantId}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("Exceção ao obter access token Google: " . $e->getMessage());
        }

        return null;
    }

    public function createMeeting(
        int $assistantId,
        string $title,
        string $description,
        string $startDateTime,
        string $endDateTime,
        string $agentEmail,
        string $clientEmail,
        array $additionalEmails = []
    ): ?array {
        $accessToken = $this->getAccessToken($assistantId);
        if (!$accessToken) return null;

        $calendarId = Setting::where('assistant_id', $assistantId)->where('key', 'google_calendar_id')->value('value') ?? 'primary';
        if (empty($calendarId)) $calendarId = 'primary';

        // Prepara lista única de e-mails convidados
        $attendees = [];
        $allEmails = array_unique(array_filter(array_merge([$agentEmail, $clientEmail], $additionalEmails)));

        foreach ($allEmails as $email) {
            $attendees[] = ['email' => trim($email)];
        }

        $payload = [
            'summary' => $title,
            'description' => $description,
            'start' => [
                'dateTime' => Carbon::parse($startDateTime)->toIso8601String(),
                'timeZone' => 'America/Sao_Paulo',
            ],
            'end' => [
                'dateTime' => Carbon::parse($endDateTime)->toIso8601String(),
                'timeZone' => 'America/Sao_Paulo',
            ],
            'attendees' => $attendees,
            'guestsCanSeeOtherGuests' => true,
            'guestsCanInviteOthers' => true,
            'guestsCanModify' => false,
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'req_' . time() . '_' . rand(1000, 9999),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet'
                    ]
                ]
            ]
        ];

        try {
            $url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . "/events?conferenceDataVersion=1&sendUpdates=all";
            
            $response = Http::withToken($accessToken)
                ->contentType('application/json')
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $meetLink = $data['hangoutLink'] ?? ($data['conferenceData']['entryPoints'][0]['uri'] ?? null);

                return [
                    'event_id' => $data['id'] ?? null,
                    'meet_link' => $meetLink,
                    'raw' => $data
                ];
            }

            Log::error("Erro na criação do evento Google Calendar: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("Exceção ao criar evento no Google Calendar: " . $e->getMessage());
        }

        return null;
    }
}