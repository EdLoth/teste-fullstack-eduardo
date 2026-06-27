<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
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
