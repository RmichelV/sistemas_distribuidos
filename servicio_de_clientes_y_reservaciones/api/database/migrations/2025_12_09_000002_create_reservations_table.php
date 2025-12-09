<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id'); // Sin FK - validación HTTP
            $table->decimal('total_amount', 10, 2);
            $table->decimal('advance_amount', 10, 2);
            $table->decimal('rest_amount', 10, 2);
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->string('pay_type'); // efectivo, tarjeta, transferencia
            $table->unsignedBigInteger('branch_id'); // Sin FK - validación HTTP
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed
            $table->date('reservation_date');
            $table->date('pickup_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
