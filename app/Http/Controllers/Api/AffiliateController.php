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