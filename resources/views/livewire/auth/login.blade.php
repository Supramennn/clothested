<div class="mx-auto w-full max-w-md">
    <header class="mb-6 text-center">
        <h1 class="type-display-lg text-black">Login</h1>
        <p class="mt-2 text-sm text-zinc-500">Welcome back. Enter your account details.</p>
    </header>

    <form wire:submit="login" class="card-elevated space-y-4 p-6 sm:p-8">
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

        <p class="border-t border-zinc-200 pt-4 text-center text-sm text-zinc-500">
            New here?
            <a href="{{ route('register') }}" class="font-[550] text-black underline underline-offset-4 hover:text-zinc-600">Create an account</a>
        </p>
    </form>
</div>
