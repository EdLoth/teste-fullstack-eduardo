<?php

namespace App\Console\Commands;

use App\Jobs\SyncOrdersJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncOrders extends Command
{
    protected $signature   = 'orders:sync';
    protected $description = 'Sincroniza pedidos da Fake Store API';

    public function handle(): void
    {
        $this->info('Iniciando sincronização...');

        // Busca todos os dados necessários da API
        $carts    = Http::get('https://fakestoreapi.com/carts')->json();
        $users    = Http::get('https://fakestoreapi.com/users')->json();
        $products = Http::get('https://fakestoreapi.com/products')->json();

        if (empty($carts)) {
            $this->error('Nenhum carrinho encontrado na API.');
            return;
        }

        $this->info('Encontrados: ' . count($carts) . ' pedidos, ' . count($users) . ' afiliados, ' . count($products) . ' produtos.');

        // Divide os carts em páginas de 5 e dispara um Job por página
        $pages = array_chunk($carts, 5);

        foreach ($pages as $index => $page) {
            SyncOrdersJob::dispatch($page, $users, $products)
                ->onQueue('sync');

            $this->info('Job ' . ($index + 1) . '/' . count($pages) . ' enfileirado.');
        }

        $this->info('Todos os Jobs foram enfileirados! O Worker está processando em background.');
    }
}