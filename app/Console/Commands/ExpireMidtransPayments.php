<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransException;
use App\Services\MidtransPaymentService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireMidtransPayments extends Command
{
    protected $signature = 'midtrans:expire-pending';

    protected $description = 'Expire overdue Midtrans transactions and release their reserved stock';

    public function handle(MidtransPaymentService $payments, OrderService $orders): int
    {
        Payment::query()
            ->where('transaction_status', 'pending')
            ->whereNotNull('expiry_time')
            ->where('expiry_time', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($expiredPayments) use ($payments): void {
                foreach ($expiredPayments as $payment) {
                    try {
                        $payments->expirePayment($payment);
                    } catch (MidtransException $exception) {
                        Log::error('Scheduled Midtrans expiration could not be completed.', [
                            'payment_id' => $payment->id,
                            'order_id' => $payment->midtrans_order_id,
                            'exception' => $exception->getMessage(),
                        ]);

                        continue;
                    }
                }
            });

        $cutoff = now()->subHours((int) config('clothested.payment_expiry_hours'));

        Order::query()
            ->where('status', 'pending')
            ->where('placed_at', '<=', $cutoff)
            ->whereDoesntHave('payment')
            ->orderBy('id')
            ->chunkById(100, function ($ordersWithoutPayment) use ($orders): void {
                foreach ($ordersWithoutPayment as $order) {
                    DB::transaction(function () use ($order, $orders): void {
                        $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                        if (
                            $lockedOrder->status === 'pending'
                            && $lockedOrder->placed_at <= now()->subHours((int) config('clothested.payment_expiry_hours'))
                            && ! $lockedOrder->payment()->exists()
                        ) {
                            $orders->cancel($lockedOrder);
                        }
                    });
                }
            });

        return self::SUCCESS;
    }
}
