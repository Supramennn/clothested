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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('midtrans_order_id')->unique();
            $table->string('midtrans_transaction_id')->nullable();
            $table->enum('payment_type', ['bank_transfer', 'gopay', 'qris', 'card'])->nullable();
            $table->decimal('gross_amount', 12, 2);
            $table->enum('transaction_status', ['pending', 'settlement', 'expire', 'cancel', 'deny'])->default('pending');
            $table->timestampTz('expiry_time')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestampsTz();
        });

        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->string('address_line');
            $table->string('city');
            $table->string('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
        Schema::dropIfExists('payments');
    }
};
