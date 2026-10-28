<div class="mx-auto w-full max-w-md">
    <header class="mb-6 text-center">
        <h1 class="type-display-lg text-black">Create your account</h1>
        <p class="mt-2 text-sm text-zinc-500">Registration signs you in right away as a customer.</p>
    </header>

    <form wire:submit="register" class="card-elevated space-y-4 p-6 sm:p-8">
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
                Phone <span class="font-normal text-zinc-500">(optional)</span>
            </label>
            <input id="phone" type="text" wire:model="phone" class="input">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label mb-1">Password</label>
            <input id="password" type="password" wire:model="password" class="input">
            <p class="mt-1 text-xs text-zinc-500">At least 8 characters.</p>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label mb-1">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" class="input">
            @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Register</button>

        <p class="border-t border-zinc-200 pt-4 text-center text-sm text-zinc-500">
            Already have an account?
            <a href="{{ route('login') }}" class="font-[550] text-black underline underline-offset-4 hover:text-zinc-600">Login</a>
        </p>
    </form>
</div>
