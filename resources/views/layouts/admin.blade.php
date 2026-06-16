<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CBT System' }} — {{ config('app.name', 'CBT') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <div class="d-flex" id="wrapper">
        <aside class="bg-dark text-white p-3" id="sidebar" style="min-width: 240px; min-height: 100vh;">
            <h5 class="text-white mb-4">
                <i class="bi bi-mortarboard"></i> {{ config('app.name', 'CBT') }}
            </h5>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.master.index') }}" class="nav-link text-white {{ request()->routeIs('admin.master.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-database me-2"></i> Master Data
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.students.index') }}" class="nav-link text-white {{ request()->routeIs('admin.students.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-people me-2"></i> Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.questions.index') }}" class="nav-link text-white {{ request()->routeIs('admin.questions.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-question-circle me-2"></i> Bank Soal
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.exams.index') }}" class="nav-link text-white {{ request()->routeIs('admin.exams.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-clipboard-check me-2"></i> Ujian
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sessions.index') }}" class="nav-link text-white {{ request()->routeIs('admin.sessions.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-clock-history me-2"></i> Sesi & Token
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.grading.index') }}" class="nav-link text-white {{ request()->routeIs('admin.grading.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-pencil-square me-2"></i> Koreksi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.analysis.index') }}" class="nav-link text-white {{ request()->routeIs('admin.analysis.*') ? 'active bg-primary' : '' }}">
                        <i class="bi bi-bar-chart-line me-2"></i> Analisis Soal
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong>{{ auth()->user()->name }}</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item" type="submit">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </aside>
        <main class="flex-grow-1 p-4" style="background: #f8f9fa;">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
