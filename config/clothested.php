<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Configuration
    |--------------------------------------------------------------------------
    |
    | The flat national courier rate (PRD section 7.1) and the payment window
    | applied to every pending order.
    |
    */

    'shipping_fee' => (int) env('SHIPPING_FEE_COURIER', 20000),

    'payment_expiry_hours' => (int) env('PAYMENT_EXPIRY_HOURS', 24),

];
