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
            $table->string('provider')->default('openai')->nullable();
            $table->text('system_prompt')->nullable();
            $table->string('model')->default('gpt-4o-mini')->nullable();
            $table->text('openai_api_key')->nullable();
            $table->text('gemini_api_key')->nullable();
            $table->text('anthropic_api_key')->nullable();
            $table->text('grok_api_key')->nullable();
            $table->json('knowledge_files')->nullable();
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