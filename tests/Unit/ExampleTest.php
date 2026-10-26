<?php

use App\Models\Order;

test('the order model maps to the orders table per ERD 2.1', function () {
    expect((new Order)->getTable())->toBe('orders');
});
