<div class="mx-auto w-full max-w-md">
    <header class="mb-6">
        <h1 class="type-display-lg text-black">Profile</h1>
        <p class="mt-2 text-sm text-zinc-500">Your email is the login identity and cannot be changed in v1.0.</p>
    </header>

    <form wire:submit="save" class="card space-y-4 p-6 sm:p-8">
        @if ($saved)
            <p class="alert alert-success">Profile saved.</p>
        @endif

        <div>
            <label class="field-label mb-1">Email</label>
            <input type="email" value="{{ auth()->user()?->email }}" disabled class="input">
        </div>

        <div>
            <label for="name" class="field-label mb-1">Name</label>
            <input id="name" type="text" wire:model="name" class="input">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="field-label mb-1">
                Phone <span class="font-normal text-zinc-500">(optional)</span>
            </label>
            <input id="phone" type="text" wire:model="phone" class="input">
            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Save changes</button>
    </form>
</div>
