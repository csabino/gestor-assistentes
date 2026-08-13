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
        $table->string('provider')->default('openai')->after('is_active');
        $table->text('gemini_api_key')->nullable()->after('openai_api_key');
        $table->text('anthropic_api_key')->nullable()->after('gemini_api_key');
        $table->text('grok_api_key')->nullable()->after('anthropic_api_key');
        $table->json('knowledge_files')->nullable()->after('grok_api_key');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            //
        });
    }
};
