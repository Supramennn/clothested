<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Shop fashion at ClothesTed with straightforward catalog filters, secure checkout, and real-time customer support')">
    <title>@yield('title', 'ClothesTed')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-black antialiased">
    <header class="sticky top-0 z-30 border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="font-display text-xl font-[330] tracking-tight text-black">
                ClothesTed
            </a>

            @auth
                @php($cartCount = \App\Models\CartItem::query()->where('user_id', auth()->id())->sum('quantity'))
            @endauth

            <nav class="ml-auto" aria-label="Primary">
                <details class="relative sm:hidden">
                    <summary class="btn-outline cursor-pointer list-none px-5! py-2! text-sm">Menu</summary>
                    <div class="absolute right-0 top-full z-40 mt-2 flex max-h-[calc(100dvh-6rem)] w-60 flex-col gap-1 overflow-y-auto rounded-lg border border-zinc-200 bg-white p-2 text-sm shadow-lg">
                        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Catalog</a>
                        @auth
                            <a href="{{ route('cart') }}" class="inline-flex min-h-11 items-center justify-between gap-2 rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
                                <span>Cart</span>
                                @if ($cartCount > 0)
                                    <span class="rounded-full bg-black px-2 py-0.5 text-xs font-medium text-white">{{ $cartCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('wishlist') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Wishlist</a>
                            <a href="{{ route('orders.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Orders</a>
                            @if (! in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
                                <a href="{{ route('chat') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Chat</a>
                            @endif
                            <a href="{{ route('profile') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Profile</a>
                            @if (in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
                                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="inline-flex min-h-11 w-full items-center rounded-md px-3 text-left text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Login</a>
                            <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">Register</a>
                        @endauth
                    </div>
                </details>

                <div class="hidden flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium text-zinc-600 sm:flex">
                    <a href="{{ route('home') }}" class="btn-ghost">Catalog</a>

                    @auth
                        <a href="{{ route('wishlist') }}" class="btn-ghost">Wishlist</a>
                        <a href="{{ route('orders.index') }}" class="btn-ghost">Orders</a>

                        @if (! in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
                            @livewire('chat-button')
                        @endif

                        <a href="{{ route('profile') }}" class="btn-ghost">Profile</a>

                        @if (in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
                            <a href="{{ route('admin.dashboard') }}" class="btn-ghost">Admin</a>
                        @endif
                    @endauth

                    @auth
                        <a href="{{ route('cart') }}" class="btn-primary py-2! text-sm min-h-11!" wire:key="cart-pill">
                            Cart
                            @if ($cartCount > 0)
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-xs font-medium" wire:key="cart-count">{{ $cartCount }}</span>
                            @endif
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-outline py-2! text-sm min-h-11!">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-outline py-2! text-sm min-h-11!">Login</a>
                        <a href="{{ route('register') }}" class="btn-primary py-2! text-sm min-h-11!">Register</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    @if (session('error'))
        <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6">
            <p class="alert alert-danger">{{ session('error') }}</p>
        </div>
    @endif

    @if (session('status'))
        <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6">
            <p class="alert alert-success">{{ session('status') }}</p>
        </div>
    @endif

    <main class="mx-auto w-full max-w-7xl grow px-4 py-10 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="mt-auto bg-night text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-baseline justify-between gap-x-8 gap-y-3 px-4 py-16 sm:px-6">
            <div>
                <p class="font-display text-2xl font-[330] tracking-tight">ClothesTed</p>
                <p class="mt-1 text-sm text-link-cool-1">
                    &copy; {{ date('Y') }}. ClothesTed v1.0 demo store.
                </p>
            </div>

            <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-link-cool-3 underline decoration-link-cool-3/40 underline-offset-4" aria-label="Footer">
                <a href="{{ route('home') }}" class="transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Catalog</a>
                @auth
                    <a href="{{ route('cart') }}" class="transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Cart</a>
                    <a href="{{ route('wishlist') }}" class="transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Wishlist</a>
                    <a href="{{ route('profile') }}" class="transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Profile</a>
                @else
                    <a href="{{ route('login') }}" class="transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Login</a>
                    <a href="{{ route('register') }}" class="transition-colors hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Register</a>
                @endauth
            </nav>
        </div>
    </footer>
</body>
</html>
