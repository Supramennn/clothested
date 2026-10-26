<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Shared shell for the storefront and admin pages (FR-05 nav, flash areas).
 * Livewire full-page components consume the same view through #[Layout].
 */
class AppLayout extends Component
{
    /**
     * Render the layout view; page content arrives as the slot.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
