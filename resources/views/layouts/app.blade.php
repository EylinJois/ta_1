<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title')</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    @stack('styles')

</head>

<body>

    <div class="layout-wrapper">

        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-header">

                <div class="logo">
                    Mandarin LMS
                </div>

                <button class="sidebar-toggle d-none d-lg-flex"
                    id="desktopToggle">
                    <i class="bi bi-list"></i>
                </button>

            </div>

            <ul class="menu">

                <li>
                    <a href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-fill"></i>
                        <span class="menu-text">
                            Dashboard
                        </span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <i class="bi bi-people-fill"></i>
                        <span class="menu-text">
                            Users
                        </span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <i class="bi bi-journal-bookmark-fill"></i>
                        <span class="menu-text">
                            Materials
                        </span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <i class="bi bi-pencil-square"></i>
                        <span class="menu-text">
                            Quiz & Assignment
                        </span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <i class="bi bi-calendar-event-fill"></i>
                        <span class="menu-text">
                            Calendar
                        </span>
                    </a>
                </li>

                <li>
                    <a href="#">
                        <i class="bi bi-award-fill"></i>
                        <span class="menu-text">
                            Certificates
                        </span>
                    </a>
                </li>

            </ul>

        </aside>

        {{-- Main Content --}}
        <main class="main-content" id="mainContent">

            {{-- Topbar --}}
            <div class="topbar">

                <div class="d-flex align-items-center gap-3">

                    {{-- Mobile Toggle --}}
                    <button class="mobile-toggle d-lg-none"
                        id="mobileToggle">
                        <i class="bi bi-list"></i>
                    </button>

                    <h5 class="mb-0">
                        @yield('page-title')
                    </h5>

                </div>

                <div class="profile-dropdown">

                    <div class="profile-trigger">

                        <i class="bi bi-bell-fill fs-5"></i>

                        <img src="https://i.pravatar.cc/100" alt="">

                        <div class="d-none d-md-block">
                            <strong>Admin</strong>
                        </div>

                        <i class="bi bi-chevron-down small"></i>

                    </div>

                    <div class="profile-menu">

                        <a href="#">
                            <i class="bi bi-person-circle"></i>
                            Edit Profile
                        </a>

                        <a href="#">
                            <i class="bi bi-gear"></i>
                            Settings
                        </a>

                        <hr>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="logout-btn">
                                <i class="bi bi-box-arrow-right"></i>
                                Logout
                            </button>
                        </form>

                    </div>

                </div>

            </div>

            {{-- Content --}}
            <div class="content-wrapper">
                @yield('content')
            </div>

        </main>

    </div>

    {{-- Overlay --}}
    <div class="sidebar-overlay"
        id="sidebarOverlay">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        const desktopToggle = document.getElementById('desktopToggle');

        const mobileToggle = document.getElementById('mobileToggle');

        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Desktop Collapse
        desktopToggle.addEventListener('click', () => {

            sidebar.classList.toggle('collapsed');

            mainContent.classList.toggle('expanded');

        });

        // Mobile Open
        mobileToggle.addEventListener('click', () => {

            sidebar.classList.add('mobile-show');

            sidebarOverlay.classList.add('show');

        });

        // Mobile Close
        sidebarOverlay.addEventListener('click', () => {

            sidebar.classList.remove('mobile-show');

            sidebarOverlay.classList.remove('show');

        });
    </script>

    @stack('scripts')

</body>

</html>