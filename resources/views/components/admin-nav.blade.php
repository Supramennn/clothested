<nav class="card flex flex-wrap gap-2 p-2" aria-label="Admin">
    <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Dashboard
    </a>
    <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Orders
    </a>
    <a href="{{ route('admin.chat') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Chat
    </a>
    <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Products
    </a>
    <a href="{{ route('admin.categories') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Categories
    </a>
    <a href="{{ route('admin.reports.sales') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Sales report
    </a>
    @if (auth()->user()?->role === 'super_admin')
        <a href="{{ route('admin.accounts') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
            Admin accounts
        </a>
    @endif
</nav>
