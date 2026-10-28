<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClothesTed')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-stone-100 font-sans text-stone-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="font-display text-xl font-semibold tracking-tight text-stone-900">
                ClothesTed
            </a>

            @auth
                @php($cartCount = \App\Models\CartItem::query()->where('user_id', auth()->id())->sum('quantity'))
            @endauth

            <nav class="ml-auto" aria-label="Primary">
                <details class="relative sm:hidden">
                    <summary class="btn-outline min-h-11 list-none cursor-pointer px-4 py-2.5">Menu</summary>
                    <div class="absolute right-0 top-full z-40 mt-2 flex max-h-[calc(100dvh-6rem)] w-60 flex-col gap-1 overflow-y-auto rounded-lg border border-stone-300 bg-white p-2 text-sm shadow-lg">
                        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Catalog</a>
                        @auth
                            <a href="{{ route('cart') }}" class="inline-flex min-h-11 items-center justify-between gap-2 rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                                <span>Cart</span>
                                @if ($cartCount > 0)
                                    <span class="rounded-full bg-stone-900 px-2 py-0.5 text-xs font-semibold text-white">{{ $cartCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('wishlist') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Wishlist</a>
                            <a href="{{ route('orders.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Orders</a>
                            <a href="{{ route('profile') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Profile</a>
                            @if (in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
                                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="inline-flex min-h-11 w-full items-center rounded-md px-3 text-left text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Login</a>
                            <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Register</a>
                        @endauth
                    </div>
                </details>

                <div class="hidden flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium text-stone-600 sm:flex">
                    <a href="{{ route('home') }}" class="btn-ghost">Catalog</a>

                    @auth
                        <a href="{{ route('cart') }}" class="btn-ghost inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                        </svg>
                        Cart
                        @if ($cartCount > 0)
                            <span class="rounded-full bg-stone-900 px-2 py-0.5 text-xs font-semibold text-white"
                                  wire:key="cart-count">{{ $cartCount }}</span>
                        @endif
                        </a>

                        <a href="{{ route('wishlist') }}" class="btn-ghost">Wishlist</a>
                        <a href="{{ route('orders.index') }}" class="btn-ghost">Orders</a>
                        <a href="{{ route('profile') }}" class="btn-ghost">Profile</a>

                        @if (in_array(auth()->user()?->role, ['admin', 'super_admin'], true))
                            <a href="{{ route('admin.dashboard') }}" class="btn-ghost">Admin</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">Login</a>
                        <a href="{{ route('register') }}" class="btn-outline px-4 py-1.5">Register</a>
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

    <main class="mx-auto w-full max-w-7xl grow px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="mt-auto border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-baseline justify-between gap-x-8 gap-y-3 px-4 py-6 sm:px-6">
            <div>
                <p class="font-display text-base font-semibold text-stone-900">ClothesTed</p>
                <p class="mt-1 text-xs text-stone-500">
                    &copy; {{ date('Y') }}. ClothesTed v1.0 demo store.
                </p>
            </div>

            <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-stone-600" aria-label="Footer">
                <a href="{{ route('home') }}" class="btn-ghost">Catalog</a>
                @auth
                    <a href="{{ route('cart') }}" class="btn-ghost">Cart</a>
                    <a href="{{ route('wishlist') }}" class="btn-ghost">Wishlist</a>
                    <a href="{{ route('profile') }}" class="btn-ghost">Profile</a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Login</a>
                    <a href="{{ route('register') }}" class="btn-ghost">Register</a>
                @endauth
            </nav>
        </div>
    </footer>
</body>
</html>
