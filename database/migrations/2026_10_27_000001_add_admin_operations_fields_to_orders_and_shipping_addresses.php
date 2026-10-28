<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('refund_note')->nullable();
        });

        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->string('waybill_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->dropColumn('waybill_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refund_note');
        });
    }
};
