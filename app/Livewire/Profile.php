<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Profile · ClothesTed')]
class Profile extends Component
{
    public string $name = '';

    public string $phone = '';

    public bool $saved = false;

    /**
     * FR-06: load the account details; the email stays the locked identity.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name ?? '';
        $this->phone = $user->phone ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * FR-06: a customer can change name and phone, nothing else.
     */
    public function save(): void
    {
        $validated = $this->validate();

        Auth::user()?->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.profile');
    }
}
