<?php

use App\Models\Order;

test('pedido pending pode ser aprovado', function () {
    $order = new Order(['status' => 'pending']);
    expect($order->canTransitionTo('approved'))->toBeTrue();
});

test('pedido pending pode ser cancelado', function () {
    $order = new Order(['status' => 'pending']);
    expect($order->canTransitionTo('cancelled'))->toBeTrue();
});

test('pedido approved pode ser reembolsado', function () {
    $order = new Order(['status' => 'approved']);
    expect($order->canTransitionTo('refunded'))->toBeTrue();
});

test('pedido pending nao pode ser reembolsado', function () {
    $order = new Order(['status' => 'pending']);
    expect($order->canTransitionTo('refunded'))->toBeFalse();
});

test('pedido approved nao pode ser cancelado', function () {
    $order = new Order(['status' => 'approved']);
    expect($order->canTransitionTo('cancelled'))->toBeFalse();
});

test('pedido cancelled nao permite nenhuma transicao', function () {
    $order = new Order(['status' => 'cancelled']);
    expect($order->canTransitionTo('approved'))->toBeFalse();
    expect($order->canTransitionTo('refunded'))->toBeFalse();
    expect($order->canTransitionTo('pending'))->toBeFalse();
});

test('pedido refunded nao permite nenhuma transicao', function () {
    $order = new Order(['status' => 'refunded']);
    expect($order->canTransitionTo('approved'))->toBeFalse();
    expect($order->canTransitionTo('cancelled'))->toBeFalse();
    expect($order->canTransitionTo('pending'))->toBeFalse();
});