<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Login · ClothesTed')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public ?string $authError = null;

    /**
     * FR-03: email and password both belong to the form validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * FR-03/FR-04/FR-05: verify the credentials behind the session guard,
     * rate limit repeated failures, then send the account to its home area.
     */
    public function login(): void
    {
        $validated = $this->validate();

        if ($this->ensureNotThrottled($validated['email'])) {
            return;
        }

        if (! Auth::attempt($validated)) {
            RateLimiter::hit($this->throttleKey($validated['email']), 60);

            $this->authError = 'These credentials do not match our records.';

            return;
        }

        RateLimiter::clear($this->throttleKey($validated['email']));

        $this->reset('password');

        $this->redirectAfterAuthentication();
    }

    /**
     * FR-03: cap repeated failed attempts per email and IP.
     */
    private function ensureNotThrottled(string $email): bool
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($email), 5)) {
            return false;
        }

        $secondsLeft = (int) ceil(RateLimiter::availableIn($this->throttleKey($email)));

        $this->authError = "Too many login attempts. Try again in {$secondsLeft} seconds.";

        return true;
    }

    /**
     * Limit failures against the account plus the caller IP, not just the IP.
     */
    private function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email)).'|'.request()->ip();
    }

    /**
     * FR-04: staff start at the dashboard, customers at the store home, and a
     * page requested from a protected area is restored first.
     */
    private function redirectAfterAuthentication(): void
    {
        $intended = session()->pull('url.intended');

        $fallback = Auth::user() !== null && in_array(Auth::user()->role, ['admin', 'super_admin'], true)
            ? route('admin.dashboard')
            : route('home');

        $this->redirect($intended ?? $fallback);
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
