<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AbyssalMind - Đấu Trường Kiến Thức Đại Dương')</title>
    <link rel="stylesheet" href="{{ asset('css/ocean-app.css') }}">
</head>
<body>
    <nav class="ocean-nav">
        <a href="{{ route('welcome') }}" class="brand-logo">
            <span>🌊</span>
            <span>ABYSSALMIND</span>
        </a>

        <div class="nav-links">
            <a href="{{ route('welcome') }}" class="nav-link {{ request()->routeIs('welcome') ? 'active' : '' }}">Độ Sâu Đại Dương</a>
            <a href="{{ route('leaderboard') }}" class="nav-link {{ request()->routeIs('leaderboard') ? 'active' : '' }}">🏆 Bảng Xếp Hạng</a>

            @auth
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Bảng Điều Khiển</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" style="color:#fbbf24;">⚡ Trung Tâm AI (Admin)</a>
                @endif
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="padding: 0.45rem 0.9rem; font-size:0.85rem;">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn btn-primary" style="padding: 0.45rem 1rem; font-size:0.88rem;">Đăng ký</a>
            @endauth
        </div>
    </nav>

    @if(session('success'))
        <div class="container" style="padding-bottom: 0;">
            <div style="background: rgba(34, 197, 94, 0.18); border: 1px solid #22c55e; color: #bbf7d0; padding: 0.9rem 1.25rem; border-radius: 12px;">
                ✅ {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container" style="padding-bottom: 0;">
            <div style="background: rgba(239, 68, 68, 0.18); border: 1px solid #ef4444; color: #fecaca; padding: 0.9rem 1.25rem; border-radius: 12px;">
                ⚠️ {{ session('error') }}
            </div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>