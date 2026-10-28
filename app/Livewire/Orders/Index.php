<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * FR-39: the signed-in customer's own order history.
 */
#[Layout('layouts.app')]
#[Title('Orders · ClothesTed')]
class Index extends Component
{
    use WithPagination;

    /**
     * Own orders only, newest placement first.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', Auth::id())
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.orders.index');
    }
}
