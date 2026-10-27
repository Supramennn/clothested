<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/midtrans/callback', WebhookController::class)->name('midtrans.callback');
