@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('content')

{{-- Welcome Section --}}
<div class="welcome-banner mb-4">

    <div>
        <h2>
            Welcome Back, Admin
        </h2>

        <p class="mb-0">
            Monitor classes, students, assignments, and Mandarin learning activities.
        </p>
    </div>

    <div>
        <button class="btn btn-light">
            <i class="bi bi-plus-circle"></i>
            Create Course
        </button>
    </div>

</div>

{{-- Statistic Cards --}}
<div class="row g-4 mb-4">

    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card">

            <div>
                <p class="card-label">
                    Total Students
                </p>

                <h2>320</h2>

                <span class="growth-up">
                    +12% this month
                </span>
            </div>

            <div class="card-icon">
                <i class="bi bi-people-fill"></i>
            </div>

        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card">

            <div>
                <p class="card-label">
                    Teachers
                </p>

                <h2>24</h2>

                <span class="growth-up">
                    +4 new teachers
                </span>
            </div>

            <div class="card-icon">
                <i class="bi bi-person-workspace"></i>
            </div>

        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card">

            <div>
                <p class="card-label">
                    Active Classes
                </p>

                <h2>18</h2>

                <span class="growth-up">
                    +3 classes
                </span>
            </div>

            <div class="card-icon">
                <i class="bi bi-journal-richtext"></i>
            </div>

        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="dashboard-card">

            <div>
                <p class="card-label">
                    Assignments
                </p>

                <h2>48</h2>

                <span class="growth-down">
                    5 pending review
                </span>
            </div>

            <div class="card-icon">
                <i class="bi bi-pencil-square"></i>
            </div>

        </div>
    </div>

</div>

{{-- Main Dashboard Content --}}
<div class="row">

    {{-- Student Activity --}}
    <div class="col-lg-8 mb-4">

        <div class="table-card">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="section-title mb-0">
                    Latest Student Activity
                </h4>

                <button class="btn btn-theme btn-sm">
                    View All
                </button>

            </div>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Progress</th>
                        <th>Status</th>
                    </tr>

                    </thead>

                    <tbody>

                    <tr>

                        <td>
                            <div class="student-info">

                                <img src="https://i.pravatar.cc/100?img=1">

                                <div>
                                    <strong>Aileen Joyce</strong>
                                    <br>
                                    <small class="text-muted">
                                        HSK Beginner
                                    </small>
                                </div>

                            </div>
                        </td>

                        <td>Mandarin Speaking</td>

                        <td>

                            <div class="progress">

                                <div class="progress-bar bg-danger"
                                     style="width: 80%">
                                </div>

                            </div>

                        </td>

                        <td>
                            <span class="badge bg-success">
                                Active
                            </span>
                        </td>

                    </tr>

                    <tr>

                        <td>
                            <div class="student-info">

                                <img src="https://i.pravatar.cc/100?img=2">

                                <div>
                                    <strong>Jonathan</strong>
                                    <br>
                                    <small class="text-muted">
                                        HSK 3
                                    </small>
                                </div>

                            </div>
                        </td>

                        <td>Listening Practice</td>

                        <td>

                            <div class="progress">

                                <div class="progress-bar bg-warning"
                                     style="width: 60%">
                                </div>

                            </div>

                        </td>

                        <td>
                            <span class="badge bg-warning">
                                Pending
                            </span>
                        </td>

                    </tr>

                    <tr>

                        <td>
                            <div class="student-info">

                                <img src="https://i.pravatar.cc/100?img=3">

                                <div>
                                    <strong>Sarah</strong>
                                    <br>
                                    <small class="text-muted">
                                        HSK 2
                                    </small>
                                </div>

                            </div>
                        </td>

                        <td>Mandarin Grammar</td>

                        <td>

                            <div class="progress">

                                <div class="progress-bar bg-success"
                                     style="width: 90%">
                                </div>

                            </div>

                        </td>

                        <td>
                            <span class="badge bg-success">
                                Completed
                            </span>
                        </td>

                    </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-4">

        {{-- Upcoming Schedule --}}
        <div class="table-card mb-4">

            <h4 class="section-title">
                Upcoming Schedule
            </h4>

            <div class="schedule-item">

                <div class="schedule-icon">
                    <i class="bi bi-camera-video-fill"></i>
                </div>

                <div>
                    <strong>Speaking Practice</strong>

                    <p class="mb-0 text-muted">
                        July 5 • 09:00 AM
                    </p>
                </div>

            </div>

            <div class="schedule-item">

                <div class="schedule-icon">
                    <i class="bi bi-pencil-fill"></i>
                </div>

                <div>
                    <strong>HSK Quiz</strong>

                    <p class="mb-0 text-muted">
                        July 7 • 01:00 PM
                    </p>
                </div>

            </div>

            <div class="schedule-item">

                <div class="schedule-icon">
                    <i class="bi bi-file-earmark-arrow-up-fill"></i>
                </div>

                <div>
                    <strong>Assignment Deadline</strong>

                    <p class="mb-0 text-muted">
                        July 9 • 11:59 PM
                    </p>
                </div>

            </div>

        </div>

        {{-- Quick Actions --}}
        <div class="table-card">

            <h4 class="section-title">
                Quick Actions
            </h4>

            <div class="d-grid gap-3">

                <button class="btn btn-theme">
                    <i class="bi bi-upload"></i>
                    Upload Material
                </button>

                <button class="btn btn-orange">
                    <i class="bi bi-plus-circle"></i>
                    Create Quiz
                </button>

                <button class="btn btn-dark-custom">
                    <i class="bi bi-calendar-plus"></i>
                    Add Schedule
                </button>

            </div>

        </div>

    </div>

</div>

@endsection