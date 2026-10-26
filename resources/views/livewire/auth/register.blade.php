<div class="mx-auto w-full max-w-md">
    <header class="mb-6 text-center">
        <h1 class="font-display text-3xl font-semibold tracking-tight text-stone-900">Create your account</h1>
        <p class="mt-2 text-sm text-stone-500">Registration signs you in right away as a customer.</p>
    </header>

    <form wire:submit="register" class="card space-y-4 p-6 sm:p-8">
        <div>
            <label for="name" class="field-label mb-1">Name</label>
            <input id="name" type="text" wire:model="name" class="input">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="field-label mb-1">Email</label>
            <input id="email" type="email" wire:model="email" class="input">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="field-label mb-1">
                Phone <span class="font-normal text-stone-400">(optional)</span>
            </label>
            <input id="phone" type="text" wire:model="phone" class="input">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label mb-1">Password</label>
            <input id="password" type="password" wire:model="password" class="input">
            <p class="mt-1 text-xs text-stone-400">At least 8 characters.</p>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label mb-1">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" class="input">
            @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Register</button>

        <p class="border-t border-stone-200 pt-4 text-center text-sm text-stone-500">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-sky-700 hover:text-sky-800 hover:underline">Login</a>
        </p>
    </form>
</div>
