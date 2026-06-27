<?php

use App\Jobs\SyncOrdersJob;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('orders sync importa afiliados produtos e pedidos', function () {
    Http::fake([
        'fakestoreapi.com/carts'    => Http::response(fakeCarts(), 200),
        'fakestoreapi.com/users'    => Http::response(fakeUsers(), 200),
        'fakestoreapi.com/products' => Http::response(fakeProducts(), 200),
    ]);

    Queue::fake();

    $this->artisan('orders:sync')->assertExitCode(0);

    Queue::assertPushed(SyncOrdersJob::class);

    // Executa o Job diretamente para verificar persistência
    $job = new SyncOrdersJob(fakeCarts(), fakeUsers(), fakeProducts());
    $job->handle();

    expect(Affiliate::count())->toBe(1);
    expect(Product::count())->toBe(1);
    expect(Order::count())->toBe(1);
});

test('orders sync e idempotente — rodar duas vezes nao duplica dados', function () {
    Http::fake([
        'fakestoreapi.com/carts'    => Http::response(fakeCarts(), 200),
        'fakestoreapi.com/users'    => Http::response(fakeUsers(), 200),
        'fakestoreapi.com/products' => Http::response(fakeProducts(), 200),
    ]);

    // Roda o Job diretamente duas vezes
    $job = new SyncOrdersJob(fakeCarts(), fakeUsers(), fakeProducts());
    $job->handle();
    $job->handle();

    expect(Order::count())->toBe(1);
    expect(Affiliate::count())->toBe(1);
    expect(Product::count())->toBe(1);
});

function fakeCarts(): array
{
    return [[
        'id'       => 1,
        'userId'   => 1,
        'date'     => '2024-01-01',
        'products' => [
            ['productId' => 1, 'quantity' => 2],
        ],
    ]];
}

function fakeUsers(): array
{
    return [[
        'id'    => 1,
        'name'  => ['firstname' => 'João', 'lastname' => 'Silva'],
        'email' => 'joao@teste.com',
        'phone' => '11999999999',
        'address' => [
            'city'    => 'São Paulo',
            'zipcode' => '01310-100',
        ],
    ]];
}

function fakeProducts(): array
{
    return [[
        'id'          => 1,
        'title'       => 'Produto Teste',
        'price'       => 99.90,
        'description' => 'Descrição do produto teste',
        'category'    => 'electronics',
        'image'       => 'https://fakestoreapi.com/img/test.jpg',
    ]];
}