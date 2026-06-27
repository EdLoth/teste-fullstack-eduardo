<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Affiliate;
use App\Repositories\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\OrderStatusChanged;

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

    public function getMetrics(array $filters = []): array
    {
        // Filtros de data não são cacheados — consultas específicas demais
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $metrics = $this->repository->getMetrics($filters);
            return (array) $metrics[0];
        }

        return Cache::remember('orders:metrics', 300, function () {
            $metrics = $this->repository->getMetrics([]);
            return (array) $metrics[0];
        });
    }

    public function updateStatus(Order $order, string $newStatus, ?string $reason = null): Order
    {
        if (!$order->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Transição inválida: não é possível mover de '{$order->status}' para '{$newStatus}'."
            );
        }

        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $newStatus, $reason, $previousStatus) {
            $order->update(['status' => $newStatus]);

            $order->statusLogs()->create([
                'previous_status' => $previousStatus,
                'new_status'      => $newStatus,
                'changed_by'      => 'system',
                'reason'          => $reason,
            ]);
        });

        $this->flushMetricsCache();

        // Dispara evento — listener enfileira o webhook pro N8N
        event(new OrderStatusChanged($order, $previousStatus, $newStatus));

        return $order->fresh(['items.product', 'statusLogs']);
    }

    private function flushMetricsCache(): void
    {
        try {
            Cache::forget('orders:metrics');
        } catch (\Exception $e) {
            Log::warning('flushMetricsCache falhou: ' . $e->getMessage());
        }
    }

    public function getAffiliateSummary(int $affiliateId): array
    {
        $affiliate = Affiliate::findOrFail($affiliateId);
        $summary   = $this->repository->getAffiliateSummary($affiliateId);

        return array_merge(['affiliate' => $affiliate], $summary);
    }
}
