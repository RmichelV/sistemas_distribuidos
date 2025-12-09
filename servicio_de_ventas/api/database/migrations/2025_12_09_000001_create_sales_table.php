<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_code')->unique();
            $table->string('customer_name');
            $table->date('sale_date');
            $table->string('pay_type'); // efectivo, tarjeta, transferencia
            $table->decimal('final_price', 10, 2);
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id'); // Sin FK - validación HTTP
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
