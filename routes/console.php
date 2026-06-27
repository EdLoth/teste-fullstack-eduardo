<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    Cache::put('worker:heartbeat', now()->toIso8601String(), now()->addMinutes(2));
})->everyMinute();