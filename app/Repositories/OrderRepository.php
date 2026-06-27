<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Order::with(['affiliate', 'items.product'])
            ->withoutTrashed();

        if (!empty($filters['affiliate_id'])) {
            $query->where('affiliate_id', $filters['affiliate_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['min_value'])) {
            $query->where('total', '>=', $filters['min_value']);
        }

        if (!empty($filters['max_value'])) {
            $query->where('total', '<=', $filters['max_value']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('affiliate', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            })->orWhere('id', $filters['search']);
        }

        $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = ['id', 'total', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');

        return $query->paginate(20);
    }

    public function findWithDetails(int $id): ?Order
    {
        return Order::with(['affiliate', 'items.product', 'statusLogs'])
            ->find($id);
    }

    public function getMetrics(array $filters = []): array
    {
        $where  = "WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['date_from'])) {
            $where   .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where   .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        return DB::select("
        SELECT
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'approved' THEN total ELSE 0 END) as total_revenue,
            SUM(CASE WHEN status = 'pending'  THEN total ELSE 0 END) as pending_revenue,
            SUM(CASE WHEN status = 'refunded' THEN total ELSE 0 END) as refunded_revenue,
            AVG(CASE WHEN status IN ('approved','refunded') THEN total ELSE NULL END) as average_ticket,
            SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'approved'  THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status = 'refunded'  THEN 1 ELSE 0 END) as refunded_count
        FROM orders
        {$where}
    ", $params);
    }

    public function getAffiliateSummary(int $affiliateId): array
    {
        $result = DB::select("
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('approved','refunded') THEN total ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status IN ('approved','refunded') THEN total ELSE NULL END) as average_ticket,
                ROUND(
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2
                ) as cancellation_rate
            FROM orders
            WHERE affiliate_id = ? AND deleted_at IS NULL
        ", [$affiliateId]);

        return $result[0] ? (array) $result[0] : [];
    }

    public function upsert(array $data): Order
    {
        return Order::updateOrCreate(
            ['external_id' => $data['external_id']],
            $data
        );
    }
}
