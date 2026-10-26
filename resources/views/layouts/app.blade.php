<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClothesTed')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-sky-700">ClothesTed</a>

            <nav class="flex items-center gap-4 text-sm font-medium text-slate-600">
                <a href="{{ route('home') }}" class="hover:text-sky-700">Catalog</a>
                @auth
                    <a href="{{ route('cart') }}" class="hover:text-sky-700">
                        Cart
                        @if (($cartCount = \App\Models\CartItem::query()->where('user_id', auth()->id())->sum('quantity')) > 0)
                            ({{ $cartCount }})
                        @endif
                    </a>
                    <a href="{{ route('wishlist') }}" class="hover:text-sky-700">Wishlist</a>
                    <a href="{{ route('profile') }}" class="hover:text-sky-700">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:text-sky-700">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-sky-700">Login</a>
                    <a href="{{ route('register') }}"
                       class="rounded-md bg-sky-600 px-3 py-1.5 text-white hover:bg-sky-500">Register</a>
                @endauth
            </nav>
        </div>
    </header>

    @if (session('error'))
        <div class="mx-auto mt-4 w-full max-w-6xl px-4">
            <p class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    @if (session('status'))
        <div class="mx-auto mt-4 w-full max-w-6xl px-4">
            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</p>
        </div>
    @endif

    <main class="mx-auto w-full max-w-6xl grow px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-sm text-slate-500">
        &copy; {{ date('Y') }} ClothesTed — demo fashion store.
    </footer>
</body>
</html>
