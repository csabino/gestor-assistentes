<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function handle(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->update($request);
        }

        return $this->index();
    }

    public function index()
    {
        $timezones = \DateTimeZone::listIdentifiers();

        $currentTz = Setting::where('key', 'timezone')->value('value') ?? 'America/Sao_Paulo';
        $webhookUrl = Setting::where('key', 'omni_webhook_url')->value('value') ?? '';

        return view('settings.index', compact('timezones', 'currentTz', 'webhookUrl'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'timezone' => 'required|string',
            'omni_webhook_url' => 'nullable|url',
        ]);

        Setting::updateOrCreate(['key' => 'timezone'], ['value' => $request->input('timezone')]);
        Setting::updateOrCreate(['key' => 'omni_webhook_url'], ['value' => $request->input('omni_webhook_url')]);

        return redirect()->to('/?view=settings')->with('success', 'Configurações atualizadas!');
    }
}