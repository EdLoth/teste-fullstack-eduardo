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
            $table->unsignedBigInteger('external_id')->unique(); // ID do cart na fakestoreapi
            $table->unsignedBigInteger('affiliate_id');
            $table->enum('status', ['pending', 'approved', 'cancelled', 'refunded'])
                  ->default('pending');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes(); // requisito do teste

            // Foreign key
            $table->foreign('affiliate_id')
                  ->references('id')
                  ->on('affiliates')
                  ->cascadeOnDelete();

            // Índice composto — requisito do teste
            // Otimiza filtros por affiliate_id + status + created_at
            $table->index(['affiliate_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};