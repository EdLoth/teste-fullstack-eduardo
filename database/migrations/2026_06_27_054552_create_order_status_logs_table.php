<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->enum('previous_status', ['pending', 'approved', 'cancelled', 'refunded'])
                  ->nullable(); // null quando é o status inicial
            $table->enum('new_status', ['pending', 'approved', 'cancelled', 'refunded']);
            $table->string('changed_by')->nullable(); // usuário responsável (auditoria)
            $table->text('reason')->nullable(); // motivo da mudança
            $table->timestamps();

            // Se o pedido for deletado, logs são deletados junto
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};