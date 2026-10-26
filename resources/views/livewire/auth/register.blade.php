<div class="mx-auto w-full max-w-md">
    <h1 class="mb-1 text-2xl font-bold text-slate-900">Create your account</h1>
    <p class="mb-6 text-sm text-slate-500">Registration signs you in right away as a customer.</p>

    <form wire:submit="register" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input id="name" type="text" wire:model="name"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">
                Phone <span class="font-normal text-slate-400">(optional)</span>
            </label>
            <input id="phone" type="text" wire:model="phone"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            <p class="mt-1 text-xs text-slate-400">At least 8 characters.</p>
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500">
            Register
        </button>

        <p class="text-center text-sm text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-sky-700 hover:underline">Login</a>
        </p>
    </form>
</div>
