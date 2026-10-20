<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Mandarin LMS</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    {{-- Custom CSS --}}
    <link rel="stylesheet"
          href="{{ asset('css/welcome.css') }}">

</head>
<body>

{{-- Navbar --}}
<nav class="landing-navbar">

    <div class="container">

        <div class="navbar-content">

            <div class="brand-logo">
                Mandarin LMS
            </div>

            <div class="nav-buttons">

                <a href="{{ route('login') }}"
                   class="btn btn-login">
                    Login
                </a>

                <a href="{{ route('register') }}"
                   class="btn btn-register">
                    Register
                </a>

            </div>

        </div>

    </div>

</nav>

{{-- Hero Section --}}
<section class="hero-section">

    <div class="container">

        <div class="row align-items-center min-vh-100">

            {{-- Left Content --}}
            <div class="col-lg-6 mb-5 mb-lg-0 mt-5">

                <div class="hero-content">

                    <h1>
                        Learn Mandarin with Modern TTS Technology
                    </h1>

                    <p>
                        Access video lessons, quizzes,
                        Mandarin pronounciation TTS, assignments,
                        calendars, notes, and HSK progress tracking
                        in one platform.
                    </p>

                    <div class="hero-buttons">

                        <a href="{{ route('register') }}"
                           class="btn btn-hero-primary">
                            Get Started
                        </a>

                        <a href="{{ route('login') }}"
                           class="btn btn-hero-secondary">
                            Login
                        </a>

                    </div>

                    {{-- Features --}}
                    <div class="hero-features">

                        <div class="feature-item">
                            <i class="bi bi-play-circle-fill"></i>
                            Video Learning
                        </div>

                        <div class="feature-item">
                            <i class="bi bi-volume-up-fill"></i>
                            Mandarin Audio TTS
                        </div>

                        <div class="feature-item">
                            <i class="bi bi-pencil-square"></i>
                            Quiz & Assignment
                        </div>

                    </div>

                </div>

            </div>

            {{-- Right Image --}}
            <div class="col-lg-6">

                <div class="hero-image-wrapper">

                    <div class="hero-card hero-card-1">

                        <i class="bi bi-journal-richtext"></i>

                        <h5>Interactive Materials</h5>

                        <p>
                            EPUB, PDF, Text & Video
                        </p>

                    </div>

                    <div class="hero-card hero-card-2">

                        <i class="bi bi-translate"></i>

                        <h5>Mandarin Practice</h5>

                        <p>
                            AI Voice & TTS Conversation
                        </p>

                    </div>

                    <div class="hero-main-circle">

                        <i class="bi bi-mortarboard-fill"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

{{-- Features Section --}}
<section class="features-section">

    <div class="container">

        <div class="section-header text-center">

            <span class="section-badge">
                LMS FEATURES
            </span>

            <h2>
                Everything Needed for Mandarin Learning
            </h2>

        </div>

        <div class="row g-4">

            <div class="col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        <i class="bi bi-play-btn-fill"></i>
                    </div>

                    <h4>
                        Interactive Materials
                    </h4>

                    <p>
                        Learn using videos, PDFs,
                        EPUB, audio, and text materials.
                    </p>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        <i class="bi bi-mic-fill"></i>
                    </div>

                    <h4>
                        Mandarin TTS
                    </h4>

                    <p>
                        Practice pronunciation and
                        conversations using AI voice.
                    </p>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>

                    <h4>
                        Smart Learning Calendar
                    </h4>

                    <p>
                        Track assignments, deadlines,
                        and upcoming classes.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

{{-- CTA Section --}}
<section class="cta-section">

    <div class="container">

        <div class="cta-box">

            <h2>
                Start Learning Mandarin Today
            </h2>

            <p>
                Join students and teachers using
                Mandarin LMS for interactive learning.
            </p>

            <a href="{{ route('register') }}"
               class="btn btn-light btn-lg">
                Create Account
            </a>

        </div>

    </div>

</section>

</body>
</html>