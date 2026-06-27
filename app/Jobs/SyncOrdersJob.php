<?php

namespace App\Jobs;

use App\Models\Affiliate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOrdersJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private array $carts,
        private array $users,
        private array $products,
    ) {}

    public function handle(): void
    {
        // Indexa users e products por ID para acesso rápido
        $usersById    = collect($this->users)->keyBy('id');
        $productsById = collect($this->products)->keyBy('id');

        foreach ($this->carts as $cart) {
            DB::transaction(function () use ($cart, $usersById, $productsById) {

                // 1. Upsert do afiliado
                $user = $usersById[$cart['userId']] ?? null;
                if (!$user) return;

                $affiliate = Affiliate::updateOrCreate(
                    ['external_id' => (string) $user['id']],
                    [
                        'name'  => $user['name']['firstname'] . ' ' . $user['name']['lastname'],
                        'email' => $user['email'],
                        'phone' => $user['phone'] ?? null,
                        'city'  => $user['address']['city'] ?? null,
                        'state' => null,
                        'zipcode' => $user['address']['zipcode'] ?? null,
                    ]
                );

                // 2. Upsert dos produtos e calcula o total do pedido
                $total = 0;
                $items = [];

                foreach ($cart['products'] as $cartProduct) {
                    $productData = $productsById[$cartProduct['productId']] ?? null;
                    if (!$productData) continue;

                    $product = Product::updateOrCreate(
                        ['external_id' => $productData['id']],
                        [
                            'title'       => $productData['title'],
                            'price'       => $productData['price'],
                            'description' => $productData['description'] ?? null,
                            'category'    => $productData['category'] ?? null,
                            'image_url'   => $productData['image'] ?? null,
                        ]
                    );

                    $quantity = $cartProduct['quantity'];
                    $price    = $productData['price'];
                    $total   += $quantity * $price;

                    $items[] = [
                        'product_id' => $product->id,
                        'quantity'   => $quantity,
                        'price'      => $price,
                    ];
                }

                // 3. Upsert do pedido
                $order = Order::updateOrCreate(
                    ['external_id' => $cart['id']],
                    [
                        'affiliate_id' => $affiliate->id,
                        'status'       => 'pending',
                        'total'        => round($total, 2),
                    ]
                );

                // 4. Recria os itens do pedido (evita duplicatas)
                $order->items()->delete();
                foreach ($items as $item) {
                    $order->items()->create($item);
                }

                // 5. Cria log inicial se não existir
                if ($order->wasRecentlyCreated) {
                    $order->statusLogs()->create([
                        'previous_status' => null,
                        'new_status'      => 'pending',
                        'changed_by'      => 'system',
                        'reason'          => 'Importado via orders:sync',
                    ]);
                }
            });
        }

        Log::info('SyncOrdersJob: ' . count($this->carts) . ' pedidos processados.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncOrdersJob falhou: ' . $exception->getMessage());
    }
}