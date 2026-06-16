<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Ujian' }} — CBT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @stack('head')
    <style>
        body { background: #f4f6fa; }
        .question-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .option-btn { display: block; width: 100%; text-align: left; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px; background: white; cursor: pointer; transition: all 0.15s; }
        .option-btn:hover { border-color: #6366f1; background: #f5f7ff; }
        .option-btn.selected { border-color: #4f46e5; background: #eef2ff; }
        .option-btn .opt-letter { display: inline-block; width: 28px; height: 28px; line-height: 28px; text-align: center; background: #f3f4f6; border-radius: 50%; margin-right: 12px; font-weight: 600; }
        .option-btn.selected .opt-letter { background: #4f46e5; color: white; }
        .nav-btn { width: 36px; height: 36px; border-radius: 6px; border: 1px solid #d1d5db; background: white; cursor: pointer; }
        .nav-btn.answered { background: #10b981; color: white; border-color: #10b981; }
        .nav-btn.current { background: #4f46e5; color: white; border-color: #4f46e5; }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
