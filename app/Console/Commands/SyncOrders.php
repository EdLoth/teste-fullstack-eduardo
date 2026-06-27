<?php

namespace App\Console\Commands;

use App\Jobs\SyncOrdersJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class SyncOrders extends Command
{
    protected $signature   = 'orders:sync';
    protected $description = 'Sincroniza pedidos da Fake Store API';

    // Rate limit: máximo 5 requisições por segundo
    private const RATE_LIMIT_KEY      = 'fakestoreapi';
    private const RATE_LIMIT_MAX      = 5;
    private const RATE_LIMIT_DECAY    = 1;

    public function handle(): void
    {
        $this->info('Iniciando sincronização...');

        $carts    = $this->fetchWithRateLimit('https://fakestoreapi.com/carts');
        $users    = $this->fetchWithRateLimit('https://fakestoreapi.com/users');
        $products = $this->fetchWithRateLimit('https://fakestoreapi.com/products');

        if (empty($carts)) {
            $this->error('Nenhum carrinho encontrado na API.');
            return;
        }

        $this->info("Encontrados: " . count($carts) . " pedidos, " . count($users) . " afiliados, " . count($products) . " produtos.");

        $pages = array_chunk($carts, 5);

        foreach ($pages as $index => $page) {
            SyncOrdersJob::dispatch($page, $users, $products)
                ->onQueue('sync');

            $this->info('Job ' . ($index + 1) . '/' . count($pages) . ' enfileirado.');
        }

        $this->info('Todos os Jobs foram enfileirados! O Worker está processando em background.');
    }

    private function fetchWithRateLimit(string $url): array
    {
        // Aguarda se o rate limit foi atingido
        while (RateLimiter::tooManyAttempts(self::RATE_LIMIT_KEY, self::RATE_LIMIT_MAX)) {
            $seconds = RateLimiter::availableIn(self::RATE_LIMIT_KEY);
            $this->line("Rate limit atingido — aguardando {$seconds}s...");
            sleep($seconds ?: 1);
        }

        RateLimiter::hit(self::RATE_LIMIT_KEY, self::RATE_LIMIT_DECAY);

        return Http::get($url)->json() ?? [];
    }
}