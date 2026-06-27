<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class AffiliateController extends Controller
{
    public function __construct(
        private OrderService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/affiliates/{id}/summary",
     *     summary="Resumo do afiliado",
     *     tags={"Affiliates"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Resumo com total de pedidos, receita, ticket médio e taxa de cancelamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="affiliate", type="object"),
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="total_revenue", type="number"),
     *                 @OA\Property(property="average_ticket", type="number"),
     *                 @OA\Property(property="cancellation_rate", type="number")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Afiliado não encontrado")
     * )
     */
    public function summary(int $id): JsonResponse
    {
        try {
            $summary = $this->service->getAffiliateSummary($id);

            return response()->json(['data' => $summary]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'data'   => null,
                'errors' => ['Afiliado não encontrado.'],
            ], 404);
        }
    }
}