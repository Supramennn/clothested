<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin accounts | ClothesTed')]
class AdminAccounts extends Component
{
    public ?int $editingAdminId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public ?string $notice = null;

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function admins(): Collection
    {
        return User::query()->where('role', 'admin')->orderBy('name')->get();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingAdminId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => $this->editingAdminId === null
                ? ['required', 'string', 'min:8']
                : ['nullable', 'string', 'min:8'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();
        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
        ];

        if (($validated['password'] ?? '') !== '') {
            $attributes['password'] = $validated['password'];
        }

        if ($this->editingAdminId === null) {
            User::query()->create($attributes + ['role' => 'admin']);
            $this->notice = 'Admin account created.';
        } else {
            User::query()
                ->where('role', 'admin')
                ->findOrFail($this->editingAdminId)
                ->update($attributes);
            $this->notice = 'Admin account updated.';
        }

        unset($this->admins);
        $this->resetForm();
    }

    public function editAdmin(int $adminId): void
    {
        $admin = User::query()->where('role', 'admin')->findOrFail($adminId);

        $this->editingAdminId = $admin->id;
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->phone = $admin->phone ?? '';
        $this->password = '';
        $this->notice = null;
        $this->resetValidation();
    }

    public function deleteAdmin(int $adminId): void
    {
        $admin = User::query()->where('role', 'admin')->findOrFail($adminId);

        if ($admin->orders()->exists()) {
            $this->addError('admins', 'This account has order history and cannot be deleted.');

            return;
        }

        $admin->delete();
        unset($this->admins);
        $this->notice = 'Admin account deleted.';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->notice = null;
    }

    private function resetForm(): void
    {
        $this->reset('editingAdminId', 'name', 'email', 'phone', 'password');
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.admin-accounts');
    }
}
