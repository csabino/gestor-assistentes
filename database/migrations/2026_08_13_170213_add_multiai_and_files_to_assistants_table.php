<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            if (!Schema::hasColumn('assistants', 'provider')) {
                $table->string('provider')->default('openai')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'system_prompt')) {
                $table->text('system_prompt')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'model')) {
                $table->string('model')->default('gpt-4o-mini')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'openai_api_key')) {
                $table->text('openai_api_key')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'gemini_api_key')) {
                $table->text('gemini_api_key')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'anthropic_api_key')) {
                $table->text('anthropic_api_key')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'grok_api_key')) {
                $table->text('grok_api_key')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'knowledge_files')) {
                $table->json('knowledge_files')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn([
                'provider', 'system_prompt', 'model', 
                'openai_api_key', 'gemini_api_key', 'anthropic_api_key', 
                'grok_api_key', 'knowledge_files'
            ]);
        });
    }
};