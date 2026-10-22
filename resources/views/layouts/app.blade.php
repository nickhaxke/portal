<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Executive E-Library') }} — @yield('title', 'Curated Leadership & Lifelong Learning')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="site-body" x-data="publicNavigation" @keydown.escape.window="close(true)">
    <a href="#app" class="skip-link">Skip to content</a>
    <!-- Header -->
    <header class="site-header" @click.outside="close()">
        <a href="{{ route('home') }}" class="logo">
            <span style="display:inline-flex; align-items:center;"><x-icon name="emblem" size="20" /></span> Executive <small style="font: 700 9px 'DM Sans', sans-serif; letter-spacing: 2px; align-self: flex-end; margin: 0 0 5px -6px">E-LIBRARY</small>
        </a>

        <nav id="nav" x-ref="primaryNav" aria-label="Primary navigation" :class="{ 'open': mobileNav }">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('libraries.index') }}" class="{{ request()->routeIs('libraries.*') ? 'active' : '' }}">Libraries</a>
            <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore') ? 'active' : '' }}">Catalogue</a>
            <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">Disciplines</a>
            <a href="{{ route('insights.index') }}" class="{{ request()->routeIs('insights.*') ? 'active' : '' }}">Insights</a>
            <a href="{{ route('library') }}" class="{{ request()->routeIs('library') ? 'active' : '' }}">My Shelf</a>
            
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="gold" style="padding: 8px 16px; font-size: 13px;">Admin Portal</a>
                @else
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="outline" style="padding: 8px 16px; font-size: 13px;">Logout</button>
                    </form>
                @endif
            @else
                <a href="{{ route('login') }}" class="outline">Executive Login</a>
            @endauth
        </nav>

        <button type="button" class="menu" x-ref="menuToggle" @click="toggle()"
            :aria-expanded="mobileNav" aria-controls="nav" aria-label="Toggle navigation"><x-icon name="menu" size="20" /></button>
    </header>

    @if(session('success'))
        <div class="wrap" style="padding-top: 15px;">
            <div style="background: #e6f9ed; border: 1px solid #b7ebd0; color: #117838; padding: 12px 18px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; justify-content: space-between;">
                <span style="display:inline-flex; align-items:center; gap:8px;"><x-icon name="check" size="16" /> {{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" style="background:none; border:0; color:#117838; font-size:18px; display:inline-flex; align-items:center;"><x-icon name="close" size="14" /></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="wrap" style="padding-top: 15px;">
            <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 18px; border-radius: 8px; font-size: 14px; display: flex; align-items: center; justify-content: space-between;">
                <span style="display:inline-flex; align-items:center; gap:8px;"><x-icon name="alert" size="16" /> {{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" style="background:none; border:0; color:#9b1c1c; font-size:18px; display:inline-flex; align-items:center;"><x-icon name="close" size="14" /></button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main id="app" tabindex="-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="wrap footer-inner">
            <div>
                <strong style="color: var(--navy); font-size: 16px;">Executive Intelligence & Leadership E-Library</strong>
                <p>Knowledge for better decisions · Lifelong leadership development.</p>
            </div>
            <div>
                Curated Executive Collection · Knowledge for better decisions · &copy; 2026
            </div>
        </div>
    </footer>

    <!-- Mobile Bottom Navigation Bar -->
    <div class="bottom">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
            <span style="display:inline-flex; justify-content:center; align-items:center;"><x-icon name="overview" size="18" /></span>Home
        </a>
        <a href="{{ route('explore') }}" class="{{ request()->routeIs('explore') ? 'active' : '' }}">
            <span style="display:inline-flex; justify-content:center; align-items:center;"><x-icon name="explore" size="18" /></span>Explore
        </a>
        <a href="{{ route('library') }}" class="{{ request()->routeIs('library') ? 'active' : '' }}">
            <span style="display:inline-flex; justify-content:center; align-items:center;"><x-icon name="publications" size="18" /></span>Library
        </a>
        <a href="{{ route('insights.index') }}" class="{{ request()->routeIs('insights.*') ? 'active' : '' }}">
            <span style="display:inline-flex; justify-content:center; align-items:center;"><x-icon name="insights" size="18" /></span>Insights
        </a>
        @if(auth()->check() && auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">
                <span style="display:inline-flex; justify-content:center; align-items:center;"><x-icon name="shield" size="18" /></span>Admin
            </a>
        @else
            <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">
                <span style="display:inline-flex; justify-content:center; align-items:center;"><x-icon name="readers" size="18" /></span>Login
            </a>
        @endif
    </div>

    @stack('scripts')
</body>
</html>
