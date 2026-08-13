<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            if (!Schema::hasColumn('assistants', 'whatsapp_provider')) {
                $table->string('whatsapp_provider')->nullable();
            }
            if (!Schema::hasColumn('assistants', 'whatsapp_url')) {
                $table->string('whatsapp_url')->nullable(); // URL da UaZapi, Evolution ou ChatPro
            }
            if (!Schema::hasColumn('assistants', 'whatsapp_instance')) {
                $table->string('whatsapp_instance')->nullable(); // Nome/ID da Instância
            }
            if (!Schema::hasColumn('assistants', 'whatsapp_token')) {
                $table->text('whatsapp_token')->nullable(); // Senhas/Tokens da Instância
            }
            if (!Schema::hasColumn('assistants', 'whatsapp_verify_token')) {
                $table->string('whatsapp_verify_token')->nullable(); // Para o Webhook (Meta ou Z-API)
            }
        });
    }

    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_provider', 'whatsapp_url', 'whatsapp_instance', 'whatsapp_token', 'whatsapp_verify_token']);
        });
    }
};