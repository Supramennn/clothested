<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Sales report · ClothesTed')]
class SalesReport extends Component
{
    use WithPagination;

    public string $fromDate = '';

    public string $toDate = '';

    #[Locked]
    public string $appliedFromDate = '';

    #[Locked]
    public string $appliedToDate = '';

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = today()->toDateString();
        $this->appliedFromDate = $this->fromDate;
        $this->appliedToDate = $this->toDate;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ];
    }

    public function runReport(): void
    {
        $this->validate();
        $this->appliedFromDate = $this->fromDate;
        $this->appliedToDate = $this->toDate;
        $this->resetPage();
        unset($this->paidSales, $this->paidOrderCount, $this->dailySales, $this->paidOrders);
    }

    #[Computed]
    public function paidSales(): float
    {
        return (float) $this->paidOrdersQuery()->sum('orders.total');
    }

    #[Computed]
    public function paidOrderCount(): int
    {
        return $this->paidOrdersQuery()->count('orders.id');
    }

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function dailySales(): Collection
    {
        return $this->paidOrdersQuery()
            ->selectRaw('DATE(payments.paid_at) as sales_date, COUNT(orders.id) as order_count, SUM(orders.total) as revenue')
            ->groupByRaw('DATE(payments.paid_at)')
            ->orderByDesc('sales_date')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function paidOrders(): LengthAwarePaginator
    {
        return $this->paidOrdersQuery()
            ->with(['user', 'payment'])
            ->orderByDesc('payments.paid_at')
            ->orderByDesc('orders.id')
            ->paginate(20);
    }

    /**
     * @return Builder<Order>
     */
    private function paidOrdersQuery(): Builder
    {
        return Order::query()
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->select('orders.*')
            ->whereBetween('payments.paid_at', [
                Carbon::parse($this->appliedFromDate)->startOfDay(),
                Carbon::parse($this->appliedToDate)->endOfDay(),
            ])
            ->whereNotIn('orders.status', ['cancelled'])
            ->where('payments.transaction_status', 'settlement');
    }

    public function render(): View
    {
        return view('livewire.admin.sales-report');
    }
}
