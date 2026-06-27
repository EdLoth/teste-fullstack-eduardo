<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\SendWebhookToN8NJob;

class SendOrderStatusWebhook
{
    public function handle(OrderStatusChanged $event): void
    {
        SendWebhookToN8NJob::dispatch(
            $event->order,
            $event->previousStatus,
            $event->newStatus,
        );
    }
}