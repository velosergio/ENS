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
        // Crear tabla parejas primero
        Schema::create('parejas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_ingreso')->nullable();
            $table->smallInteger('numero_equipo')->nullable();
            $table->longText('foto_base64')->nullable();
            $table->text('foto_thumbnail_50')->nullable();
            $table->text('foto_thumbnail_100')->nullable();
            $table->text('foto_thumbnail_500')->nullable();
            $table->enum('estado', ['activo', 'retirado'])->default('activo');
            $table->timestamps();
        });

        // Crear tabla users con relación a parejas
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombres')->nullable();
            $table->string('apellidos')->nullable();
            $table->string('celular', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['masculino', 'femenino'])->nullable();
            $table->longText('foto_base64')->nullable();
            $table->text('foto_thumbnail_50')->nullable();
            $table->text('foto_thumbnail_100')->nullable();
            $table->text('foto_thumbnail_500')->nullable();
            $table->foreignId('pareja_id')->nullable()->constrained('parejas')->nullOnDelete();
            $table->foreignId('equipo_id')->nullable();
            $table->enum('rol', ['mango', 'admin', 'equipista'])->default('equipista');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('parejas');
    }
};
