<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard KPI') - PT. BASS Training Center</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php($user = session('user'))

    @if($user)
        <div class="app-shell">
            <div id="sidebar-overlay" class="sidebar-overlay"></div>

            <aside id="app-sidebar" class="app-sidebar app-sidebar-panel">
                <div class="flex items-start justify-between gap-3 border-b border-white/10 px-5 pb-4 pt-6">
                    <div>
                        <div class="page-hero-meta !border-white/15 !bg-white/10 !text-white/80">Dashboard Monitoring KPI</div>
                        <h2 class="mt-4 text-[15px] font-bold leading-6">PT. BASS Training Center &amp; Consultant</h2>
                        <p class="mt-2 text-xs leading-5 text-white/65">Monitoring kinerja pegawai, progres pekerjaan, dan kualitas operasional dalam satu panel.</p>
                    </div>
                    <button id="sidebar-close" type="button" class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-white/80 transition hover:bg-white/15 lg:hidden" aria-label="Tutup menu">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 6 12 12M18 6 6 18"/>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
                    <div class="sidebar-section-title !pt-0">Menu Utama</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                        <span class="sidebar-link-icon">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 10.75L12 4l9 6.75V20a1 1 0 0 1-1 1h-5.5v-6.25h-5V21H4a1 1 0 0 1-1-1v-9.25Z"/>
                            </svg>
                        </span>
                        <span>Beranda</span>
                    </a>
                    <a href="{{ route('tasks.create') }}" class="sidebar-link {{ request()->routeIs('tasks.*') ? 'is-active' : '' }}">
                        <span class="sidebar-link-icon">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m4 15 6.5 6L20 11.5 13.5 5 4 15Z"/>
                                <path d="M12 6.5 17.5 12"/>
                            </svg>
                        </span>
                        <span>Input Pekerjaan</span>
                    </a>
                    <a href="{{ route('kpi.my') }}" class="sidebar-link {{ request()->routeIs('kpi.my') ? 'is-active' : '' }}">
                        <span class="sidebar-link-icon">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>
                            </svg>
                        </span>
                        <span>KPI Saya</span>
                    </a>
                    <a href="{{ route('kpi.ranking') }}" class="sidebar-link {{ request()->routeIs('kpi.ranking') ? 'is-active' : '' }}">
                        <span class="sidebar-link-icon">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M8 21h8M12 17v4M7 4h10v3a5 5 0 0 1-10 0V4Z"/>
                                <path d="M7 6H4a3 3 0 0 0 3 3m10-3h3a3 3 0 0 1-3 3"/>
                            </svg>
                        </span>
                        <span>Ranking Kinerja</span>
                    </a>
                    <a href="{{ route('kpi.rekap') }}" class="sidebar-link {{ request()->routeIs('kpi.rekap') ? 'is-active' : '' }}">
                        <span class="sidebar-link-icon">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M7 3h10v18H7z"/>
                                <path d="M10 8h4M10 12h4M10 16h4"/>
                            </svg>
                        </span>
                        <span>Rekap Pekerjaan</span>
                    </a>

                    @if($user->isHR())
                        <div class="mt-4 border-t border-white/10 pt-4">
                            <div class="sidebar-section-title">HR Panel</div>
                            <a href="{{ route('employees.index') }}" class="sidebar-link {{ request()->routeIs('employees.*') ? 'is-active' : '' }}">
                                <span class="sidebar-link-icon">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9.5" cy="7" r="4"/>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </span>
                                <span>Manajemen Pegawai</span>
                            </a>
                            <a href="{{ route('kpi-components.index') }}" class="sidebar-link {{ request()->routeIs('kpi-components.*') ? 'is-active' : '' }}">
                                <span class="sidebar-link-icon">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </span>
                                <span>Komponen KPI</span>
                            </a>
                            <a href="{{ route('slas.index') }}" class="sidebar-link {{ request()->routeIs('slas.*') ? 'is-active' : '' }}">
                                <span class="sidebar-link-icon">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M12 7v5l3 3"/>
                                    </svg>
                                </span>
                                <span>SLA Pekerjaan</span>
                            </a>
                            <a href="{{ route('kpi.all') }}" class="sidebar-link {{ request()->routeIs('kpi.all') ? 'is-active' : '' }}">
                                <span class="sidebar-link-icon">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 19V5m0 14h16"/>
                                        <path d="M7 15V9m5 6V5m5 10v-3"/>
                                    </svg>
                                </span>
                                <span>Rekap Semua KPI</span>
                            </a>
                        </div>
                    @endif
                </nav>

                <div class="border-t border-white/10 px-5 py-4 text-sm text-white/70">
                    <strong class="block text-[13px] text-white">{{ $user->name }}</strong>
                    <span class="text-xs">{{ $user->position }}</span>
                </div>
            </aside>

            <main class="app-main">
                <header class="app-topbar">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-3">
                            <button id="sidebar-open" type="button" class="topbar-menu-button" aria-label="Buka menu">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M4 7h16M4 12h16M4 17h16"/>
                                </svg>
                            </button>
                            <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">PT. BASS Training Center &amp; Consultant</p>
                            <h1 class="mt-1 text-xl font-bold text-slate-900">@yield('title', 'Dashboard')</h1>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 self-start md:self-auto">
                            <div class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-right md:block">
                                <div class="text-xs font-semibold text-slate-900">{{ $user->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $user->position }}</div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-secondary">Keluar</button>
                            </form>
                        </div>
                    </div>
                </header>

                <div class="page-shell">
                    @yield('content')
                </div>
            </main>
        </div>
    @else
        @yield('content')
    @endif

    <script>
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach((el) => el.remove());
        }, 3000);

        const sidebar = document.getElementById('app-sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const openSidebarButton = document.getElementById('sidebar-open');
        const closeSidebarButton = document.getElementById('sidebar-close');

        const toggleSidebar = (isOpen) => {
            if (!sidebar || !sidebarOverlay) return;

            sidebar.classList.toggle('is-open', isOpen);
            sidebarOverlay.classList.toggle('is-open', isOpen);
            document.body.classList.toggle('overflow-hidden', isOpen);
        };

        openSidebarButton?.addEventListener('click', () => toggleSidebar(true));
        closeSidebarButton?.addEventListener('click', () => toggleSidebar(false));
        sidebarOverlay?.addEventListener('click', () => toggleSidebar(false));

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                toggleSidebar(false);
            }
        });
    </script>
</body>
</html>
