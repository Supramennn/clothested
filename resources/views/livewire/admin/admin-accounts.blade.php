<div class="space-y-6">
    <x-admin-nav />

    <header>
        <p class="eyebrow">Access management</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight text-stone-900">Admin accounts</h1>
        <p class="mt-2 text-sm text-stone-600">Only super admins can create, edit, or remove store admin accounts.</p>
    </header>

    @if ($notice)
        <p class="alert alert-success" role="status">{{ $notice }}</p>
    @endif
    @error('admins')
        <p class="alert alert-danger" role="alert">{{ $message }}</p>
    @enderror

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <section class="card p-5 sm:p-6">
            <h2 class="font-display text-xl font-semibold text-stone-900">
                {{ $editingAdminId === null ? 'Create admin account' : 'Edit admin account' }}
            </h2>
            <form wire:submit="save" class="mt-5 space-y-4">
                <div>
                    <label for="admin-name" class="field-label">Name</label>
                    <input id="admin-name" type="text" wire:model="name" class="input mt-1 min-h-11">
                    @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="admin-email" class="field-label">Email</label>
                    <input id="admin-email" type="email" wire:model="email" class="input mt-1 min-h-11">
                    @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="admin-phone" class="field-label">Phone</label>
                    <input id="admin-phone" type="text" wire:model="phone" class="input mt-1 min-h-11">
                    @error('phone') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="admin-password" class="field-label">Password</label>
                    <input id="admin-password" type="password" wire:model="password" class="input mt-1 min-h-11" autocomplete="new-password">
                    <p class="mt-1 text-xs text-stone-600">
                        {{ $editingAdminId === null ? 'Use at least 8 characters.' : 'Leave blank to keep the current password.' }}
                    </p>
                    @error('password') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary min-h-11" wire:loading.attr="disabled">
                        {{ $editingAdminId === null ? 'Create account' : 'Save account' }}
                    </button>
                    @if ($editingAdminId !== null)
                        <button type="button" wire:click="cancelEdit" class="btn-outline min-h-11">Cancel edit</button>
                    @endif
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-xl font-semibold text-stone-900">Store admins</h2>
                <span class="text-sm text-stone-600">{{ $this->admins->count() }}</span>
            </div>
            <p wire:loading.flex class="alert alert-warn" role="status" aria-live="polite">Loading admin accounts…</p>

            @if ($this->admins->isEmpty())
                <div class="card px-5 py-10 text-center">
                    <h3 class="font-display text-lg font-semibold text-stone-900">No admin accounts</h3>
                    <p class="mt-2 text-sm text-stone-600">Create an admin account for store operations.</p>
                </div>
            @else
                @foreach ($this->admins as $admin)
                    <article wire:key="admin-account-{{ $admin->id }}" class="card flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <h3 class="font-medium text-stone-900">{{ $admin->name }}</h3>
                            <p class="mt-1 break-all text-sm text-stone-600">{{ $admin->email }}</p>
                            @if ($admin->phone)
                                <p class="mt-1 text-sm text-stone-600">{{ $admin->phone }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="editAdmin({{ $admin->id }})" class="btn-outline min-h-11">Edit</button>
                            <button type="button" wire:click="deleteAdmin({{ $admin->id }})"
                                    wire:confirm="Delete this admin account?"
                                    class="btn-outline min-h-11 border-red-200 text-red-700 hover:border-red-300 hover:text-red-800">
                                Delete
                            </button>
                        </div>
                    </article>
                @endforeach
            @endif
        </section>
    </div>
</div>
