<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Services\OrderService;
use App\Support\Price;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Class 3.4: CheckoutForm (method, address, note, placeOrder).
 */
#[Layout('layouts.app')]
#[Title('Checkout — ClothesTed')]
class CheckoutForm extends Component
{
    public string $shippingMethod = 'pickup';

    public string $recipientName = '';

    public string $phone = '';

    public string $addressLine = '';

    public string $city = '';

    public string $postalCode = '';

    public string $note = '';

    /**
     * FR-32/FR-34: prefill the receiver from the account so courier checkout
     * starts one step ahead; the fields stay editable for other recipients.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->recipientName = $user->name ?? '';
        $this->phone = $user->phone ?? '';
    }

    /**
     * FR-32: the account cart being checked out.
     *
     * @return Collection<int, CartItem>
     */
    #[Computed]
    public function cartItems(): Collection
    {
        return CartItem::query()
            ->where('user_id', Auth::id())
            ->with(['variant.product.images'])
            ->orderBy('id')
            ->get();
    }

    /**
     * FR-36: the running subtotal of the cart.
     */
    #[Computed]
    public function subtotal(): float
    {
        return (float) $this->cartItems->sum(
            fn (CartItem $item): float => (int) $item->quantity * $item->variant->finalPrice()
        );
    }

    /**
     * FR-35: pickup costs nothing; courier uses the flat national rate.
     */
    #[Computed]
    public function shippingFee(): float
    {
        return $this->shippingMethod === 'courier'
            ? (float) config('clothested.shipping_fee')
            : 0.0;
    }

    /**
     * FR-36: subtotal + shipping fee, what the customer confirms.
     */
    #[Computed]
    public function total(): float
    {
        return $this->subtotal + $this->shippingFee;
    }

    /**
     * FR-29: cart lines whose stock no longer covers the quantity.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function stockIssues(): Collection
    {
        return $this->cartItems
            ->filter(fn (CartItem $item): bool => $item->variant->stock < (int) $item->quantity)
            ->map(fn (CartItem $item): string => $item->variant->product->name
                .' ('.$item->variant->label().'): '
                .($item->variant->stock > 0 ? 'only '.$item->variant->stock.' left' : 'sold out'));
    }

    /**
     * FR-33/FR-34: the address block only exists for the courier method and
     * its five fields are all required, with a format check on the phone.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'shippingMethod' => ['required', 'in:pickup,courier'],
            'note' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->shippingMethod === 'courier') {
            $rules += [
                'recipientName' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
                'addressLine' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'postalCode' => ['required', 'string', 'max:20'],
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Use digits, spaces, +, -, or parentheses, with 8 to 20 characters.',
        ];
    }

    /**
     * Switching methods changes which fields are required; stale markers go away.
     */
    public function updatedShippingMethod(): void
    {
        $this->resetValidation();
    }

    /**
     * FR-37/FR-38: hand the cart to the service, then continue the customer to
     * their pending order. Payment continues from the order page in Phase 6.
     */
    public function placeOrder(OrderService $orders): void
    {
        $this->validate();

        $user = Auth::user();

        if ($user === null) {
            $this->redirect(route('login'));

            return;
        }

        $order = $orders->placeOrder($user, [
            'shipping_method' => $this->shippingMethod,
            'note' => $this->note !== '' ? $this->note : null,
            'address' => $this->shippingMethod === 'courier' ? [
                'recipient_name' => $this->recipientName,
                'phone' => $this->phone,
                'address_line' => $this->addressLine,
                'city' => $this->city,
                'postal_code' => $this->postalCode,
            ] : null,
        ]);

        $this->redirect(route('orders.show', $order->order_code));
    }

    public function render(): View
    {
        return view('livewire.checkout-form', ['price' => Price::class]);
    }
}
