<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Assistant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assistant_id')->index();
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['assistant_id', 'key']);
            });
        } elseif (!Schema::hasColumn('settings', 'assistant_id')) {
            Schema::dropIfExists('settings');
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assistant_id')->index();
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['assistant_id', 'key']);
            });
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
        
        $maxFileSize = Setting::where('assistant_id', $assistantId)->where('key', 'max_file_size_mb')->value('value') ?? '4';
        $allowedExtRaw = Setting::where('assistant_id', $assistantId)->where('key', 'allowed_extensions')->value('value');
        $allowedExtensions = $allowedExtRaw ? json_decode($allowedExtRaw, true) : [];
        if (!is_array($allowedExtensions)) {
            $allowedExtensions = [];
        }

        $currentView = 'settings';

        return view('settings.index', compact(
            'timezones', 
            'currentTz', 
            'webhookUrl', 
            'maxFileSize', 
            'allowedExtensions', 
            'currentView', 
            'assistant'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'assistant_id' => 'required|exists:assistants,id',
            'timezone' => 'required|string',
            'omni_webhook_url' => 'nullable|string',
            'max_file_size_mb' => 'required|in:1,2,4,6,8',
            'allowed_extensions' => 'nullable|array',
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

        Setting::updateOrCreate(
            ['assistant_id' => $assistantId, 'key' => 'max_file_size_mb'],
            ['value' => $request->input('max_file_size_mb', '4')]
        );

        $allowedExtensions = $request->input('allowed_extensions', []);
        Setting::updateOrCreate(
            ['assistant_id' => $assistantId, 'key' => 'allowed_extensions'],
            ['value' => json_encode(array_values($allowedExtensions))]
        );

        return redirect()->to('/?view=settings&assistant_id=' . $assistantId)->with('success', 'Configurações atualizadas para este assistente!');
    }
}