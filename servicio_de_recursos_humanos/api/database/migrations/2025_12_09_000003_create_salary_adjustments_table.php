<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('adjustment_type'); // bonus, deduction, raise, overtime
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->date('date');
            $table->string('status')->default('pending'); // pending, approved, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_adjustments');
    }
};
