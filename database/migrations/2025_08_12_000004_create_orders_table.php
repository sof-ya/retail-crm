<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
