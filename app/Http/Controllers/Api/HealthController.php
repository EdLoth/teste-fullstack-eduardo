<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Status dos serviços dependentes",
     *     tags={"Health"},
     *     @OA\Response(
     *         response=200,
     *         description="Todos os serviços operacionais",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="services", type="object",
     *                 @OA\Property(property="mysql", type="string", example="ok"),
     *                 @OA\Property(property="redis", type="string", example="ok"),
     *                 @OA\Property(property="worker", type="string", example="ok")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Um ou mais serviços com problema"
     *     )
     * )
     */
    public function __invoke(): JsonResponse
    {
        $mysql  = $this->checkMysql();
        $redis  = $this->checkRedis();
        $worker = $this->checkWorker();

        $status = ($mysql && $redis && $worker) ? 'ok' : 'degraded';

        return response()->json([
            'status'   => $status,
            'services' => [
                'mysql'  => $mysql  ? 'ok' : 'error',
                'redis'  => $redis  ? 'ok' : 'error',
                'worker' => $worker ? 'ok' : 'error',
            ],
        ], $status === 'ok' ? 200 : 503);
    }

    private function checkMysql(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function checkWorker(): bool
    {
        try {
            return Cache::has('worker:heartbeat');
        } catch (\Exception) {
            return false;
        }
    }
}