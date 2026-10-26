<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard — ClothesTed')]
class Dashboard extends Component
{
    /**
     * Placeholder route target for FR-04 and FR-07; Phase 7 (FR-48) fills
     * this page with the sales summary and the order queue.
     */
    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
