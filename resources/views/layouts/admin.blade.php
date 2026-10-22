<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Executive Admin — @yield('title', 'Control Center')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .sidebar-section-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #71839b;
            font-weight: 700;
            padding: 16px 20px 6px;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 20px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }
        .admin-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }
        .admin-nav-link.active {
            color: var(--gold);
            background: rgba(185, 130, 39, 0.12);
            border-left-color: var(--gold);
            font-weight: 600;
        }
        .admin-nav-icon {
            width: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .badge-nav-count {
            margin-left: auto;
            background: rgba(255,255,255,0.12);
            color: #cbd5e1;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 10px;
        }
        .badge-nav-count.active {
            background: var(--gold);
            color: #102946;
        }
    </style>
    @stack('styles')
</head>
<body style="background: #f6f7f9;">

    <div class="admin-shell">
        <!-- Mobile Backdrop -->
        <div id="adminSidebarBackdrop" class="admin-sidebar-backdrop"></div>

        <!-- Sidebar -->
        <aside id="adminSidebar" class="admin-sidebar">
            <div class="admin-sidebar-header">
                <div>
                    <a href="{{ route('admin.dashboard') }}" style="text-decoration:none; color:white; display:flex; align-items:center; gap:9px; font-family:'Playfair Display', serif; font-size:18px; font-weight:700; padding:0; border:0;">
                        <span style="color:var(--gold); display:inline-flex; align-items:center;"><x-icon name="emblem" size="22" /></span> Executive Admin
                    </a>
                    <small style="color:#8a99ad; font-size:10px; letter-spacing:1.2px; display:block; margin-top:3px; text-transform:uppercase; font-weight:600;">Control Center</small>
                </div>
                <button type="button" id="adminSidebarClose" class="admin-sidebar-close" aria-label="Close navigation" style="background:transparent; border:0; color:#cbd5e1; font-size:18px; cursor:pointer; padding:2px 6px; line-height:1; display:inline-flex; align-items:center;">
                    <x-icon name="close" size="18" />
                </button>
            </div>

            <nav class="admin-sidebar-nav">
                <!-- 1. OVERVIEW -->
                <div class="sidebar-section-label">Overview</div>
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="overview" size="17" /></span>
                    <span>1. Overview</span>
                </a>

                <!-- 2. LIBRARY MANAGEMENT -->
                <div class="sidebar-section-label">Library Management</div>
                <a href="{{ route('admin.libraries.index') }}" class="admin-nav-link {{ request()->routeIs('admin.libraries.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="library" size="17" /></span>
                    <span>Libraries</span>
                </a>
                <a href="{{ route('admin.books.index') }}" class="admin-nav-link {{ request()->routeIs('admin.books.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="publications" size="17" /></span>
                    <span>2. Publications</span>
                </a>
                <a href="{{ route('admin.imports.index') }}" class="admin-nav-link {{ request()->routeIs('admin.imports.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="import" size="17" /></span>
                    <span>3. Bulk Import & Calibre</span>
                </a>
                <a href="{{ route('admin.authors.index') }}" class="admin-nav-link {{ request()->routeIs('admin.authors.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="authors" size="17" /></span>
                    <span>4. Authors & Publishers</span>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="categories" size="17" /></span>
                    <span>5. Disciplines & Categories</span>
                </a>

                <!-- 3. CONTENT & EDITORIAL -->
                <div class="sidebar-section-label">Content & Editorial</div>
                <a href="{{ route('admin.insights.index') }}" class="admin-nav-link {{ request()->routeIs('admin.insights.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="insights" size="17" /></span>
                    <span>6. Executive Insights</span>
                </a>
                <a href="{{ route('admin.collections.index') }}" class="admin-nav-link {{ request()->routeIs('admin.collections.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="collections" size="17" /></span>
                    <span>7. Curated Collections</span>
                </a>
                <a href="{{ route('admin.hero-slides.index') }}" class="admin-nav-link {{ request()->routeIs('admin.hero-slides.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="explore" size="17" /></span>
                    <span>Hero Showcase Slider</span>
                </a>

                <!-- 4. PEOPLE & ACCESS -->
                <div class="sidebar-section-label">People & Access</div>
                <a href="{{ route('admin.readers.index') }}" class="admin-nav-link {{ request()->routeIs('admin.readers.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="readers" size="17" /></span>
                    <span>8. Readers & Members</span>
                </a>
                <a href="{{ route('admin.administrators.index') }}" class="admin-nav-link {{ request()->routeIs('admin.administrators.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="administrators" size="17" /></span>
                    <span>9. Administrators & Roles</span>
                </a>

                <!-- 5. REPORTS & GOVERNANCE -->
                <div class="sidebar-section-label">Reports & Governance</div>
                <a href="{{ route('admin.reports.index') }}" class="admin-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="reports" size="17" /></span>
                    <span>10. Reports & Analytics</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="admin-nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="audit" size="17" /></span>
                    <span>11. Activity & Audit Logs</span>
                </a>

                <!-- 6. SYSTEM & SETTINGS -->
                <div class="sidebar-section-label">System & Settings</div>
                <a href="{{ route('admin.settings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="settings" size="17" /></span>
                    <span>12. Portal Settings</span>
                </a>
                <a href="{{ route('admin.backups.index') }}" class="admin-nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><x-icon name="backups" size="17" /></span>
                    <span>13. System & Backups</span>
                </a>

                <!-- 7. PUBLIC ACCESS -->
                <div class="sidebar-section-label">Public Access</div>
                <a href="{{ route('home') }}" target="_blank" class="admin-nav-link">
                    <span class="admin-nav-icon"><x-icon name="globe" size="17" /></span>
                    <span>Public Portal ↗</span>
                </a>
                <a href="{{ route('explore') }}" target="_blank" class="admin-nav-link">
                    <span class="admin-nav-icon"><x-icon name="explore" size="17" /></span>
                    <span>Explore Library ↗</span>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <strong style="color:white; font-size:13px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ auth()->user()->name }}
                        </strong>
                        <small style="color:var(--gold); font-size:11px; text-transform:capitalize; font-weight:600;">
                            {{ auth()->user()->role ?? 'Administrator' }}
                        </small>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin:0; flex-shrink: 0;">
                        @csrf
                        <button type="submit" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18); color:#cbd5e1; border-radius:4px; padding:5px 9px; font-size:11px; font-weight:600; cursor:pointer;" title="Sign out">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="admin-content">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="admin-topbar-context" style="display: flex; align-items: center; gap: 14px;">
                    <button type="button" id="adminSidebarToggle" class="admin-menu-toggle" aria-label="Open Navigation" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:6px 10px; cursor:pointer; color:var(--navy); font-size:16px; display:inline-flex; align-items:center;">
                        <x-icon name="menu" size="18" />
                    </button>
                    <div style="font-size: 13px; color: var(--muted); display: flex; align-items: center; gap: 6px;">
                        <a href="{{ route('admin.dashboard') }}" style="color: var(--muted); text-decoration: none;">Control Center</a>
                        <span>/</span>
                        <span style="color: var(--navy); font-weight: 600;">@yield('header_title', 'Dashboard')</span>
                    </div>
                </div>

                <div class="admin-topbar-actions" style="display: flex; align-items: center; gap: 12px;">
                    @yield('header_actions')
                    <a href="{{ route('admin.books.create') }}" class="gold" style="padding: 8px 16px; font-size: 13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <span>+</span> New Publication
                    </a>
                </div>
            </header>

            <!-- Alerts & Content -->
            <div class="admin-main">
                @if(session('success'))
                    <div style="background: #e6f9ed; border: 1px solid #b7ebd0; color: #117838; padding: 14px 20px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display:inline-flex; align-items:center; gap:8px;"><x-icon name="check" size="16" /> {{ session('success') }}</span>
                        <button onclick="this.parentElement.remove()" style="background:none; border:0; color:#117838; font-size:18px; cursor:pointer; display:inline-flex; align-items:center;"><x-icon name="close" size="14" /></button>
                    </div>
                @endif

                @if(session('error'))
                    <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 14px 20px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display:inline-flex; align-items:center; gap:8px;"><x-icon name="alert" size="16" /> {{ session('error') }}</span>
                        <button onclick="this.parentElement.remove()" style="background:none; border:0; color:#9b1c1c; font-size:18px; cursor:pointer; display:inline-flex; align-items:center;"><x-icon name="close" size="14" /></button>
                    </div>
                @endif

                @if(session('info'))
                    <div style="background: #eef6ff; border: 1px solid #bcd8ff; color: #1e429f; padding: 14px 20px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display:inline-flex; align-items:center; gap:8px;"><x-icon name="info" size="16" /> {{ session('info') }}</span>
                        <button onclick="this.parentElement.remove()" style="background:none; border:0; color:#1e429f; font-size:18px; cursor:pointer; display:inline-flex; align-items:center;"><x-icon name="close" size="14" /></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 14px 20px; border-radius: 8px; font-size: 14px; margin-bottom: 24px;">
                        <strong style="display:block; margin-bottom: 6px;">Please correct the following errors:</strong>
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggle = document.getElementById('adminSidebarToggle');
            var sidebar = document.getElementById('adminSidebar');
            var backdrop = document.getElementById('adminSidebarBackdrop');
            var closeBtn = document.getElementById('adminSidebarClose');

            function openSidebar() {
                if (sidebar) sidebar.classList.add('open');
                if (backdrop) backdrop.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('open');
                if (backdrop) backdrop.classList.remove('open');
                document.body.style.overflow = '';
            }

            if (toggle) toggle.addEventListener('click', openSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
                    closeSidebar();
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
