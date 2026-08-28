<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
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

        return $this->index();
    }

    private function ensureTableExists()
    {
        if (!Schema::hasTable('settings')) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    public function index()
    {
        $timezones = \DateTimeZone::listIdentifiers();

        $currentTz = Setting::where('key', 'timezone')->value('value') ?? 'America/Sao_Paulo';
        $webhookUrl = Setting::where('key', 'omni_webhook_url')->value('value') ?? '';
        $currentView = 'settings';

        return view('settings.index', compact('timezones', 'currentTz', 'webhookUrl', 'currentView'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'timezone' => 'required|string',
            'omni_webhook_url' => 'nullable|string',
        ]);

        $webhookUrl = trim($request->input('omni_webhook_url') ?? '');
        if ($webhookUrl !== '') {
            $webhookUrl = rtrim($webhookUrl, '/') . '/';
        }

        Setting::updateOrCreate(['key' => 'timezone'], ['value' => $request->input('timezone')]);
        Setting::updateOrCreate(['key' => 'omni_webhook_url'], ['value' => $webhookUrl]);

        return redirect()->to('/?view=settings')->with('success', 'Configurações atualizadas!');
    }
}