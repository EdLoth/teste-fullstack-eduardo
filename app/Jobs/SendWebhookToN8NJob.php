<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookToN8NJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 5;

    public function __construct(
        private Order  $order,
        private string $previousStatus,
        private string $newStatus,
    ) {}

    public function handle(): void
    {
        $webhookUrl = config('services.n8n.webhook_url');

        if (!$webhookUrl) {
            Log::warning('N8N_WEBHOOK_URL não configurada — webhook não enviado.');
            return;
        }

        $payload = [
            'event'           => 'order.status_changed',
            'order_id'        => $this->order->id,
            'affiliate_id'    => $this->order->affiliate_id,
            'previous_status' => $this->previousStatus,
            'new_status'      => $this->newStatus,
            'total_value'     => (float) $this->order->total,
            'occurred_at'     => now()->toIso8601String(),
        ];

        Http::timeout(10)->post($webhookUrl, $payload);
    }

    public function failed(\Throwable $exception): void
    {
        // Falha no envio ao N8N não impacta o fluxo principal
        Log::error('SendWebhookToN8NJob falhou: ' . $exception->getMessage());
    }
}