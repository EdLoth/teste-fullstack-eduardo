<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListOrdersRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $service
    ) {}

    public function index(ListOrdersRequest $request): JsonResponse
    {
        $orders = $this->service->listOrders($request->validated());

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->service->getOrder($id);

        if (!$order) {
            return response()->json([
                'data'   => null,
                'errors' => ['Pedido não encontrado.'],
            ], 404);
        }

        return response()->json(['data' => $order]);
    }

    public function metrics(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getMetrics($request->only(['date_from', 'date_to'])),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'data'   => null,
                'errors' => ['Pedido não encontrado.'],
            ], 404);
        }

        try {
            $order = $this->service->updateStatus(
                $order,
                $request->validated('status'),
                $request->validated('reason'),
            );

            return response()->json(['data' => $order]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'data'   => null,
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }
}