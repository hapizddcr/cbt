<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1a3a6c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CBT">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>{{ $title ?? 'Ujian' }} — CBT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @stack('head')
    <style>
        :root {
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        html, body { height: 100%; overscroll-behavior: none; touch-action: manipulation; }
        body { background: #f4f6fa; padding-top: var(--safe-top); padding-bottom: var(--safe-bottom); }
        .question-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .option-btn { display: block; width: 100%; text-align: left; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 10px; margin-bottom: 10px; background: white; cursor: pointer; transition: all 0.15s; min-height: 52px; font-size: 16px; -webkit-tap-highlight-color: transparent; }
        .option-btn:hover { border-color: #6366f1; background: #f5f7ff; }
        .option-btn:active { transform: scale(0.99); }
        .option-btn.selected { border-color: #4f46e5; background: #eef2ff; }
        .option-btn .opt-letter { display: inline-block; width: 32px; height: 32px; line-height: 32px; text-align: center; background: #f3f4f6; border-radius: 50%; margin-right: 12px; font-weight: 600; }
        .option-btn.selected .opt-letter { background: #4f46e5; color: white; }
        .nav-btn { width: 40px; height: 40px; border-radius: 8px; border: 1px solid #d1d5db; background: white; cursor: pointer; font-weight: 600; -webkit-tap-highlight-color: transparent; }
        .nav-btn.answered { background: #10b981; color: white; border-color: #10b981; }
        .nav-btn.current { background: #4f46e5; color: white; border-color: #4f46e5; }

        /* Mobile-first responsive tweaks */
        @media (max-width: 768px) {
            .question-card { padding: 16px; border-radius: 8px; }
            .option-btn { padding: 12px 14px; font-size: 15px; }
            .option-btn .opt-letter { width: 28px; height: 28px; line-height: 28px; margin-right: 10px; }
            .navbar-brand { font-size: 0.95rem; max-width: 50%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .nav-btn { width: 36px; height: 36px; font-size: 14px; }
            .container-fluid { padding-left: 12px; padding-right: 12px; }
        }
        @media (max-width: 480px) {
            .question-card { padding: 14px; }
            .option-btn { padding: 10px 12px; min-height: 48px; }
        }

        /* Prevent text selection during exam */
        .no-select { user-select: none; -webkit-user-select: none; }
        .question-content { user-select: text; -webkit-user-select: text; }
    </style>
</head>
<body class="no-select">
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => null);
            });
        }
    </script>
</body>
</html>
