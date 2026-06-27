<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Affiliate;
use App\Repositories\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private OrderRepository $repository
    ) {}

    public function listOrders(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function getOrder(int $id): ?Order
    {
        return $this->repository->findWithDetails($id);
    }

    public function getMetrics(): array
    {
        return Cache::remember('orders:metrics', 300, function () {
            $metrics = $this->repository->getMetrics();
            return (array) $metrics[0];
        });
    }

    public function updateStatus(Order $order, string $newStatus, ?string $reason = null): Order
    {
        // Verifica se a transição é válida
        if (!$order->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transição inválida: não é possível mover de '{$order->status}' para '{$newStatus}'."
            );
        }

        DB::transaction(function () use ($order, $newStatus, $reason) {
            $previousStatus = $order->status;

            // Atualiza o status do pedido
            $order->update(['status' => $newStatus]);

            // Registra no log de auditoria
            $order->statusLogs()->create([
                'previous_status' => $previousStatus,
                'new_status'      => $newStatus,
                'changed_by'      => 'system',
                'reason'          => $reason,
            ]);

            // Invalida o cache de métricas
            Cache::forget('orders:metrics');
        });

        return $order->fresh(['items.product', 'statusLogs']);
    }

    public function getAffiliateSummary(int $affiliateId): array
    {
        $affiliate = Affiliate::findOrFail($affiliateId);
        $summary   = $this->repository->getAffiliateSummary($affiliateId);

        return array_merge(['affiliate' => $affiliate], $summary);
    }
}