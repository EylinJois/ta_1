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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs" type="module"></script>
    <script src="https://cdn.jsdelivr.net/npm/epubjs/dist/epub.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/6jg5r5fxnxszclixu4n46rn1zod6g8l2yu2ah7fcauadjalp/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

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
                    <a href="{{ route('courses.index') }}">
                        <i class="bi bi-book-fill"></i>
                        <span class="menu-text">
                            Courses
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
                        @if(auth()->user()->img)
                        <img src="{{ asset('storage/' . auth()->user()->img) }}"
                            alt="Profile Image"
                            class="profile-image">
                        @else
                        <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}"
                            alt="Profile Image"
                            class="profile-image">
                        @endif

                        <div class="d-none d-md-block">
                            <strong>Admin</strong>
                        </div>

                        <i class="bi bi-chevron-down small"></i>

                    </div>

                    <div class="profile-menu">

                        <a href="{{ route('profile.edit') }}">
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

        // =========================
        // LOAD SAVED STATE
        // =========================

        if (localStorage.getItem('sidebar-collapsed') === 'true') {

            sidebar.classList.add('collapsed');

            mainContent.classList.add('expanded');

        }

        // =========================
        // DESKTOP TOGGLE
        // =========================

        desktopToggle.addEventListener('click', () => {

            sidebar.classList.toggle('collapsed');

            mainContent.classList.toggle('expanded');

            // Save state
            localStorage.setItem(
                'sidebar-collapsed',
                sidebar.classList.contains('collapsed')
            );

        });

        // =========================
        // MOBILE OPEN
        // =========================

        mobileToggle.addEventListener('click', () => {

            sidebar.classList.add('mobile-show');

            sidebarOverlay.classList.add('show');

        });

        // =========================
        // MOBILE CLOSE
        // =========================

        sidebarOverlay.addEventListener('click', () => {

            sidebar.classList.remove('mobile-show');

            sidebarOverlay.classList.remove('show');

        });
    </script>

    @stack('scripts')

</body>

</html>