<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Assistant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function handle(Request $request)
    {
        $this->ensureTableExists();

        if ($request->isMethod('post')) {
            return $this->update($request);
        }

        return $this->index($request);
    }

    private function ensureTableExists()
    {
        // Se a tabela não tiver a coluna assistant_id, roda o fresh/migrate force
        if (!Schema::hasColumn('settings', 'assistant_id')) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    public function index(Request $request)
    {
        $assistantId = $request->input('assistant_id');
        
        if (!$assistantId) {
            return redirect('/')->with('error', 'Selecione um assistente para acessar as configurações avançadas.');
        }

        $assistant = Assistant::findOrFail($assistantId);
        $timezones = \DateTimeZone::listIdentifiers();

        $currentTz = Setting::where('assistant_id', $assistantId)->where('key', 'timezone')->value('value') ?? 'America/Sao_Paulo';
        $webhookUrl = Setting::where('assistant_id', $assistantId)->where('key', 'omni_webhook_url')->value('value') ?? '';
        
        $currentView = 'settings';

        return view('settings.index', compact('timezones', 'currentTz', 'webhookUrl', 'currentView', 'assistant'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'assistant_id' => 'required|exists:assistants,id',
            'timezone' => 'required|string',
            'omni_webhook_url' => 'nullable|string',
        ]);

        $assistantId = $request->input('assistant_id');
        $webhookUrl = trim($request->input('omni_webhook_url') ?? '');
        
        if ($webhookUrl !== '') {
            $webhookUrl = rtrim($webhookUrl, '/') . '/';
        }

        Setting::updateOrCreate(
            ['assistant_id' => $assistantId, 'key' => 'timezone'],
            ['value' => $request->input('timezone')]
        );
        
        Setting::updateOrCreate(
            ['assistant_id' => $assistantId, 'key' => 'omni_webhook_url'],
            ['value' => $webhookUrl]
        );

        return redirect()->to('/?view=settings&assistant_id=' . $assistantId)->with('success', 'Configurações atualizadas para este assistente!');
    }
}