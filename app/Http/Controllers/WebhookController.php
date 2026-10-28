<?php

namespace App\Http\Controllers;

use App\Services\MidtransException;
use App\Services\MidtransPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WebhookController
{
    public function __invoke(Request $request, MidtransPaymentService $payments): JsonResponse
    {
        try {
            $payments->processNotification($request->json()->all());

            // Observability requirement: every accepted webhook notification
            // leaves one structured line for later audits.
            Log::info('A Midtrans notification was processed.', [
                'order_id' => $request->input('order_id'),
                'transaction_status' => $request->input('transaction_status'),
            ]);
        } catch (MidtransException $exception) {
            Log::error('Midtrans notification processing failed.', [
                'order_id' => $request->input('order_id'),
                'exception' => $exception->getMessage(),
            ]);
        } catch (QueryException|ValidationException $exception) {
            Log::error('Midtrans notification could not be persisted.', [
                'order_id' => $request->input('order_id'),
                'exception' => $exception->getMessage(),
            ]);
        }

        return response()->json(['received' => true]);
    }
}
