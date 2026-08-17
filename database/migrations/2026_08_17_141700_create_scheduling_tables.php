<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Desativa a checagem de FK para permitir o drop sem travar no MariaDB
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('human_agents');
        Schema::dropIfExists('departments');
        Schema::enableForeignKeyConstraints();

        // 1. Tabela de Departamentos
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tabela de Agentes Humanos
        Schema::create('human_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('work_hours')->nullable();
            $table->text('cc_emails')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Tabela de Agendamentos
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('human_agent_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_email');
            $table->json('guest_emails')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('human_agents');
        Schema::dropIfExists('departments');
        Schema::enableForeignKeyConstraints();
    }
};