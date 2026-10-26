<div class="mx-auto w-full max-w-md">
    <header class="mb-6 text-center">
        <h1 class="font-display text-3xl font-semibold tracking-tight text-stone-900">Login</h1>
        <p class="mt-2 text-sm text-stone-500">Welcome back. Enter your account details.</p>
    </header>

    <form wire:submit="login" class="card space-y-4 p-6 sm:p-8">
        @if ($authError)
            <p class="alert alert-danger">{{ $authError }}</p>
        @endif

        <div>
            <label for="email" class="field-label mb-1">Email</label>
            <input id="email" type="email" wire:model="email" class="input">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label mb-1">Password</label>
            <input id="password" type="password" wire:model="password" class="input">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Login</button>

        <p class="border-t border-stone-200 pt-4 text-center text-sm text-stone-500">
            New here?
            <a href="{{ route('register') }}" class="font-medium text-sky-700 hover:text-sky-800 hover:underline">Create an account</a>
        </p>
    </form>
</div>
