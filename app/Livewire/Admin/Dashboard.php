<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard | ClothesTed')]
class Dashboard extends Component
{
    #[Computed]
    public function paidSalesToday(): float
    {
        return (float) Order::query()
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('payment', fn ($query) => $query
                ->where('transaction_status', 'settlement')
                ->whereBetween('paid_at', [today()->startOfDay(), today()->endOfDay()]))
            ->sum('total');
    }

    #[Computed]
    public function openOrderCount(): int
    {
        return Order::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function openOrders(): Collection
    {
        return Order::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['user', 'payment'])
            ->withCount('items')
            ->orderBy('placed_at')
            ->orderBy('id')
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
