<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->unsignedBigInteger('role_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('base_salary', 10, 2)->nullable();
                $table->date('hire_date')->nullable();
                $table->rememberToken();
                $table->timestamps();
                // No FK cross-servicio para branch_id
                $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
