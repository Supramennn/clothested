<div class="mx-auto w-full max-w-md">
    <h1 class="mb-1 text-2xl font-bold text-slate-900">Profile</h1>
    <p class="mb-6 text-sm text-slate-500">Your email is the login identity and cannot be changed in v1.0.</p>

    <form wire:submit="save" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @if ($saved)
            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">Profile saved.</p>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input type="email" value="{{ auth()->user()?->email }}" disabled
                   class="w-full rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500">
        </div>

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input id="name" type="text" wire:model="name"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">
                Phone <span class="font-normal text-slate-400">(optional)</span>
            </label>
            <input id="phone" type="text" wire:model="phone"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500">
            Save changes
        </button>
    </form>
</div>
