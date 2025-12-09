<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTA IMPORTANTE SOBRE MICROSERVICIOS:
     * - branch_id: Mantiene la estructura original pero SIN constraint foreign key
     *   porque la tabla 'branches' está en otro microservicio (branch-service)
     * - Se valida a nivel de aplicación mediante HTTP request al branch-service
     * - role_id: SÍ tiene foreign key porque 'roles' está en este mismo servicio
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('phone');
            
            // branch_id: Mantiene estructura original, sin constraint por microservicios
            // En el proyecto original: $table->foreignId("branch_id")->constrained("branches");
            // Aquí: Sin constrained() porque branches está en branch-service
            $table->foreignId("branch_id"); // Crea unsignedBigInteger con índice
            
            // role_id: Constraint interno (tabla en este mismo servicio)
            $table->foreignId('role_id')->constrained('roles');
            
            $table->integer('base_salary');
            $table->date('hire_date');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
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
    }
};
