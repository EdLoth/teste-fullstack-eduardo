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

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Lista pedidos paginados",
     *     tags={"Orders"},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","approved","cancelled","refunded"})),
     *     @OA\Parameter(name="affiliate_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="min_value", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_value", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"id","total","status","created_at"})),
     *     @OA\Parameter(name="sort_dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de pedidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Detalhe de um pedido",
     *     tags={"Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Pedido com itens e histórico de status"),
     *     @OA\Response(response=404, description="Pedido não encontrado")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/orders/metrics",
     *     summary="Métricas agregadas dos pedidos",
     *     tags={"Orders"},
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(
     *         response=200,
     *         description="Métricas com cache Redis de 5 minutos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="total_revenue", type="number"),
     *                 @OA\Property(property="pending_revenue", type="number"),
     *                 @OA\Property(property="refunded_revenue", type="number"),
     *                 @OA\Property(property="average_ticket", type="number"),
     *                 @OA\Property(property="pending_count", type="integer"),
     *                 @OA\Property(property="approved_count", type="integer"),
     *                 @OA\Property(property="cancelled_count", type="integer"),
     *                 @OA\Property(property="refunded_count", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function metrics(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getMetrics($request->only(['date_from', 'date_to'])),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/{id}/status",
     *     summary="Atualiza o status de um pedido",
     *     tags={"Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"approved","cancelled","refunded"}),
     *             @OA\Property(property="reason", type="string", example="Pagamento confirmado")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status atualizado com sucesso"),
     *     @OA\Response(response=404, description="Pedido não encontrado"),
     *     @OA\Response(response=422, description="Transição de status inválida")
     * )
     */
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