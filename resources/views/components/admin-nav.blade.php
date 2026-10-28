<nav class="card flex flex-wrap gap-2 p-2" aria-label="Admin">
    <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        Dashboard
    </a>
    <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        Orders
    </a>
    <a href="{{ route('admin.products.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        Products
    </a>
    <a href="{{ route('admin.categories') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        Categories
    </a>
    <a href="{{ route('admin.reports.sales') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
        Sales report
    </a>
    @if (auth()->user()?->role === 'super_admin')
        <a href="{{ route('admin.accounts') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
            Admin accounts
        </a>
    @endif
</nav>
