<div class="mx-auto w-full max-w-md">
    <h1 class="mb-1 text-2xl font-bold text-slate-900">Login</h1>
    <p class="mb-6 text-sm text-slate-500">Welcome back. Enter your account details.</p>

    <form wire:submit="login" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @if ($authError)
            <p class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $authError }}</p>
        @endif

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500">
            Login
        </button>

        <p class="text-center text-sm text-slate-500">
            New here?
            <a href="{{ route('register') }}" class="font-medium text-sky-700 hover:underline">Create an account</a>
        </p>
    </form>
</div>
