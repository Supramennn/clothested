<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MidtransPaymentService
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * Create the one Snap transaction for an order and return its hosted URL.
     */
    public function createSnapTransaction(Order $order): string
    {
        $this->assertConfigured();

        return DB::transaction(function () use ($order): string {
            $lockedOrder = Order::query()
                ->with(['user', 'items'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending orders can start a payment.',
                ]);
            }

            if ($lockedOrder->payment()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'A payment already exists for this order. Check its current status.',
                ]);
            }

            $expiresAt = now()->addHours((int) config('clothested.payment_expiry_hours'));
            $orderId = $lockedOrder->order_code;

            try {
                $response = $this->snapRequest()->post('/snap/v1/transactions', [
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => (int) round((float) $lockedOrder->total),
                    ],
                    'enabled_payments' => ['bank_transfer', 'gopay', 'qris', 'credit_card'],
                    'customer_details' => [
                        'first_name' => $lockedOrder->user->name,
                        'email' => $lockedOrder->user->email,
                        'phone' => $lockedOrder->shippingAddress?->phone ?? $lockedOrder->user->phone,
                    ],
                    'callbacks' => [
                        'finish' => route('orders.show', $orderId),
                        'error' => route('orders.show', $orderId),
                        'unfinish' => route('orders.show', $orderId),
                    ],
                    'expiry' => [
                        'start_time' => now()->format('Y-m-d H:i:s O'),
                        'unit' => 'hours',
                        'duration' => (int) config('clothested.payment_expiry_hours'),
                    ],
                ]);
            } catch (ConnectionException $exception) {
                Log::error('Midtrans Snap request could not connect.', [
                    'order_code' => $orderId,
                    'exception' => $exception->getMessage(),
                ]);

                throw new MidtransException('The payment service is temporarily unavailable.', previous: $exception);
            }

            $body = $this->responseBody($response, 'Snap transaction creation', $orderId);
            $redirectUrl = $body['redirect_url'] ?? null;

            if (! is_string($redirectUrl) || ! $this->isTrustedSnapUrl($redirectUrl)) {
                Log::error('Midtrans Snap returned an invalid redirect URL.', ['order_code' => $orderId]);

                throw new MidtransException('The payment service returned an invalid payment link.');
            }

            Payment::query()->create([
                'order_id' => $lockedOrder->id,
                'midtrans_order_id' => $orderId,
                'gross_amount' => $lockedOrder->total,
                'transaction_status' => 'pending',
                'expiry_time' => $expiresAt,
            ]);

            return $redirectUrl;
        });
    }

    /**
     * Reconfirm a payment from Midtrans, then apply its authoritative state.
     */
    public function refreshPayment(Payment $payment): Payment
    {
        $this->assertConfigured();

        $confirmed = $this->fetchStatus($payment->midtrans_order_id);
        $this->assertStatusMatchesPayment($payment, $confirmed);

        return $this->applyStatus($payment, $confirmed);
    }

    /**
     * Verify a notification and reconcile it only against Midtrans' status API.
     *
     * @param  array<string, mixed>  $notification
     */
    public function processNotification(array $notification): void
    {
        if (! $this->hasValidSignature($notification)) {
            Log::warning('Midtrans sent a notification with an invalid signature.', [
                'order_id' => $notification['order_id'] ?? null,
            ]);

            return;
        }

        $payment = Payment::query()
            ->where('midtrans_order_id', (string) $notification['order_id'])
            ->first();

        if ($payment === null) {
            Log::warning('Midtrans notification references an unknown payment.', [
                'order_id' => (string) $notification['order_id'],
            ]);

            return;
        }

        DB::transaction(function () use ($payment, $notification): void {
            Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->update(['raw_payload' => $notification]);
        });

        $this->assertConfigured();
        $confirmed = $this->fetchStatus($payment->midtrans_order_id);
        $this->assertStatusMatchesPayment($payment, $confirmed);
        $this->applyStatus($payment, $confirmed, $notification);
    }

    /**
     * Expire a transaction at Midtrans after its configured payment window.
     */
    public function expirePayment(Payment $payment): Payment
    {
        $this->assertConfigured();

        try {
            $response = $this->apiRequest()->post(
                '/v2/'.rawurlencode($payment->midtrans_order_id).'/expire'
            );
        } catch (ConnectionException $exception) {
            Log::error('Midtrans expiry request could not connect.', [
                'order_id' => $payment->midtrans_order_id,
                'exception' => $exception->getMessage(),
            ]);

            throw new MidtransException('The payment status could not be confirmed.', previous: $exception);
        }

        if (! $response->successful() && $response->status() !== 412) {
            $this->responseBody($response, 'transaction expiry', $payment->midtrans_order_id);
        }

        return $this->refreshPayment($payment);
    }

    /**
     * Cancel an open Midtrans transaction before releasing its reserved stock.
     */
    public function cancelPendingOrder(Order $order): void
    {
        $cancelled = DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $lockedOrder->isCancellable()) {
                return false;
            }

            $payment = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                $this->orders->cancel($lockedOrder);

                return true;
            }

            $this->assertConfigured();

            if ($payment->transaction_status !== 'pending') {
                $this->refreshPayment($payment);

                return Order::query()->findOrFail($lockedOrder->id)->status === 'cancelled';
            }

            try {
                $response = $this->apiRequest()->post(
                    '/v2/'.rawurlencode($payment->midtrans_order_id).'/cancel'
                );
            } catch (ConnectionException $exception) {
                Log::error('Midtrans cancellation request could not connect.', [
                    'order_id' => $payment->midtrans_order_id,
                    'exception' => $exception->getMessage(),
                ]);

                throw new MidtransException('The payment status could not be confirmed; the order was not cancelled.', previous: $exception);
            }

            if (! $response->successful() && $response->status() !== 412) {
                $this->responseBody($response, 'transaction cancellation', $payment->midtrans_order_id);
            }

            $this->refreshPayment($payment);

            return Order::query()->findOrFail($lockedOrder->id)->status === 'cancelled';
        });

        if (! $cancelled) {
            throw ValidationException::withMessages([
                'order' => 'This order can no longer be cancelled.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    private function hasValidSignature(array $notification): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');
        $orderId = $notification['order_id'] ?? null;
        $statusCode = $notification['status_code'] ?? null;
        $grossAmount = $notification['gross_amount'] ?? null;
        $signature = $notification['signature_key'] ?? null;

        if (
            $serverKey === ''
            || ! is_string($orderId)
            || ! is_scalar($statusCode)
            || ! is_string($grossAmount)
            || ! is_string($signature)
        ) {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchStatus(string $orderId): array
    {
        try {
            $response = $this->apiRequest()->get('/v2/'.rawurlencode($orderId).'/status');
        } catch (ConnectionException $exception) {
            Log::error('Midtrans status request could not connect.', [
                'order_id' => $orderId,
                'exception' => $exception->getMessage(),
            ]);

            throw new MidtransException('The payment status could not be confirmed.', previous: $exception);
        }

        return $this->responseBody($response, 'transaction status check', $orderId);
    }

    /**
     * @param  array<string, mixed>  $confirmed
     */
    private function assertStatusMatchesPayment(Payment $payment, array $confirmed): void
    {
        $order = $payment->order;
        $grossAmount = $confirmed['gross_amount'] ?? null;
        $confirmedOrderId = $confirmed['order_id'] ?? null;

        if (
            $order === null
            || $confirmedOrderId !== $payment->midtrans_order_id
            || ! is_string($grossAmount)
            || number_format((float) $grossAmount, 2, '.', '') !== number_format((float) $payment->gross_amount, 2, '.', '')
        ) {
            Log::error('Midtrans status response does not match the stored payment.', [
                'order_id' => $payment->midtrans_order_id,
            ]);

            throw new MidtransException('The payment status response did not match this order.');
        }
    }

    /**
     * @param  array<string, mixed>  $confirmed
     * @param  array<string, mixed>|null  $notification
     */
    private function applyStatus(Payment $payment, array $confirmed, ?array $notification = null): Payment
    {
        $rawStatus = strtolower((string) ($confirmed['transaction_status'] ?? ''));
        $status = match ($rawStatus) {
            'settlement', 'capture' => 'settlement',
            'pending' => 'pending',
            'expire' => 'expire',
            'cancel' => 'cancel',
            'deny' => 'deny',
            default => null,
        };

        if ($status === null) {
            Log::warning('Midtrans returned an unsupported transaction status.', [
                'order_id' => $payment->midtrans_order_id,
                'transaction_status' => $rawStatus,
            ]);

            return $payment->refresh();
        }

        if ($rawStatus === 'capture' && ($confirmed['fraud_status'] ?? 'accept') !== 'accept') {
            $status = 'pending';
        }

        return DB::transaction(function () use ($payment, $confirmed, $notification, $status): Payment {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $changes = [
                'midtrans_transaction_id' => $confirmed['transaction_id'] ?? $lockedPayment->midtrans_transaction_id,
                'payment_type' => $this->mapPaymentType($confirmed['payment_type'] ?? null),
            ];

            if ($notification !== null) {
                $changes['raw_payload'] = $notification;
            }

            if ($lockedPayment->transaction_status !== 'pending' && $lockedPayment->transaction_status !== $status) {
                Log::warning('Midtrans returned a conflicting final payment status.', [
                    'order_id' => $lockedPayment->midtrans_order_id,
                    'stored_status' => $lockedPayment->transaction_status,
                    'confirmed_status' => $status,
                ]);

                $lockedPayment->update($changes);

                return $lockedPayment->refresh();
            }

            $changes['transaction_status'] = $status;

            if ($status === 'settlement') {
                $changes['paid_at'] = $lockedPayment->paid_at ?? now();
            }

            $lockedPayment->update($changes);

            if ($status === 'settlement') {
                if ($order->status === 'pending') {
                    $order->update(['status' => 'processing']);
                } elseif ($order->status === 'cancelled') {
                    Log::critical('A cancelled order was settled by Midtrans; manual review is required.', [
                        'order_code' => $order->order_code,
                        'payment_id' => $lockedPayment->id,
                    ]);
                }
            } elseif (in_array($status, ['expire', 'cancel', 'deny'], true) && $order->status === 'pending') {
                $this->orders->cancel($order);
            }

            return $lockedPayment->refresh();
        });
    }

    private function mapPaymentType(mixed $paymentType): ?string
    {
        if (! is_string($paymentType)) {
            return null;
        }

        return match ($paymentType) {
            'bank_transfer' => 'bank_transfer',
            'gopay' => 'gopay',
            'qris' => 'qris',
            'credit_card' => 'card',
            default => null,
        };
    }

    private function assertConfigured(): void
    {
        if ((string) config('services.midtrans.server_key') === '') {
            Log::error('Midtrans server key is missing from the application configuration.');

            throw new MidtransException('Midtrans server key is not configured.');
        }
    }

    private function apiRequest(): PendingRequest
    {
        return Http::baseUrl((string) config('services.midtrans.api_url'))
            ->acceptJson()
            ->withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->timeout(10);
    }

    private function snapRequest(): PendingRequest
    {
        return Http::baseUrl((string) config('services.midtrans.snap_url'))
            ->acceptJson()
            ->withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->timeout(10);
    }

    private function isTrustedSnapUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $expectedHost = parse_url((string) config('services.midtrans.snap_url'), PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($host)
            && $host === $expectedHost;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseBody(Response $response, string $operation, string $orderId): array
    {
        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            Log::error('Midtrans '.$operation.' failed.', [
                'order_id' => $orderId,
                'http_status' => $response->status(),
            ]);

            throw new MidtransException('The payment service could not complete the request.');
        }

        return $body;
    }
}
