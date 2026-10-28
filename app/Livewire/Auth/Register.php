<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Register — ClothesTed')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * FR-01: name, unique email, a password of at least 8 characters, and an
     * optional phone number.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * FR-02: a successful registration creates the customer account, signs
     * the user in immediately, and lands on the store home. Non-functional
     * requirement: repeated sign-up attempts from one address are capped,
     * and successes do not reset the counter — mass account creation from
     * one address stays capped too.
     */
    public function register(): void
    {
        $this->ensureNotThrottled();

        RateLimiter::hit($this->throttleKey(), 600);

        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        Auth::login($user);

        $this->redirect(route('home'));
    }

    /**
     * Non-functional requirement: cap sign-up attempts per caller IP so an
     * address cannot create accounts faster than the store allows.
     */
    private function ensureNotThrottled(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $secondsLeft = (int) ceil(RateLimiter::availableIn($this->throttleKey()));

        $this->reset('password', 'password_confirmation');

        throw ValidationException::withMessages([
            'email' => "Too many registration attempts. Try again in {$secondsLeft} seconds.",
        ]);
    }

    /**
     * Limit sign-ups against the caller IP, not the chosen email.
     */
    private function throttleKey(): string
    {
        return 'register:'.request()->ip();
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
