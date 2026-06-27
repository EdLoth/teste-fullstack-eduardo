<?php

use App\Models\Affiliate;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('endpoint de metricas retorna estrutura correta', function () {
    $response = $this->getJson('/api/orders/metrics');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     'total_orders',
                     'total_revenue',
                     'pending_revenue',
                     'refunded_revenue',
                     'average_ticket',
                     'pending_count',
                     'approved_count',
                     'cancelled_count',
                     'refunded_count',
                 ]
             ]);
});

test('endpoint de metricas retorna zeros quando nao ha pedidos', function () {
    $response = $this->getJson('/api/orders/metrics');

    $response->assertStatus(200)
             ->assertJson([
                 'data' => [
                     'total_orders'    => 0,
                     'cancelled_count' => 0,
                 ]
             ]);
});

test('metricas sao cacheadas no redis', function () {
    Cache::forget('orders:metrics');

    // Primeira chamada — popula o cache
    $this->getJson('/api/orders/metrics');

    expect(Cache::has('orders:metrics'))->toBeTrue();
});

test('cache de metricas e invalidado ao atualizar status', function () {
    $affiliate = Affiliate::factory()->create();
    $order     = Order::factory()->create([
        'affiliate_id' => $affiliate->id,
        'status'       => 'pending',
    ]);

    // Popula o cache
    $this->getJson('/api/orders/metrics');
    expect(Cache::has('orders:metrics'))->toBeTrue();

    // Atualiza o status — deve invalidar o cache
    $this->postJson("/api/orders/{$order->id}/status", [
        'status' => 'approved',
    ]);

    expect(Cache::has('orders:metrics'))->toBeFalse();
});

test('transicao invalida retorna 422', function () {
    $affiliate = Affiliate::factory()->create();
    $order     = Order::factory()->create([
        'affiliate_id' => $affiliate->id,
        'status'       => 'approved',
    ]);

    $response = $this->postJson("/api/orders/{$order->id}/status", [
        'status' => 'cancelled',
    ]);

    $response->assertStatus(422)
             ->assertJsonStructure(['errors']);
});