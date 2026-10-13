<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Task Monitoring'))</title>
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; display: flex; flex-direction: column; margin: 0; background: #f3f5f8; color: #18283c; font: 16px/1.5 Arial, sans-serif; }
        header { background: #163653; color: white; padding: 20px max(24px, calc((100% - 1160px)/2)); }
        main { flex: 1; width: 100%; max-width: 1208px; margin: 32px auto; padding: 0 24px; }
        .header-inner, .nav-inner, .footer-inner { max-width: 1160px; margin: 0 auto; }
        .brand { color: white; text-decoration: none; font-weight: 700; font-size: 20px; }
        .header-note { margin: 4px 0 0; color: #d7e5f0; font-size: 14px; }
        nav { background: white; border-bottom: 1px solid #dce3eb; padding: 0 24px; }
        .nav-inner { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px 0; }
        nav a { display: inline-block; color: #163653; text-decoration: none; padding: 10px 16px; border-radius: 6px; }
        nav a:hover, nav a[aria-current="page"] { background: #eaf1f8; }
        a:focus-visible, .table-scroll:focus-visible { outline: 3px solid #89bde5; outline-offset: 2px; }
        .skip-link { position: absolute; top: -100px; left: 16px; background: white; padding: 12px; color: #163653; z-index: 10; }
        .skip-link:focus { top: 12px; }
        footer { border-top: 1px solid #dce3eb; background: white; padding: 20px 24px; color: #52657a; font-size: 14px; }
        footer p { margin: 0; }
        section { scroll-margin-top: 16px; }
        td { vertical-align: top; overflow-wrap: anywhere; }
        td p { margin: 6px 0 0; font-size: 14px; }
        .table-scroll table { min-width: 680px; }
        textarea { resize: vertical; }
        .grid > div { min-width: 0; }
        h1, h2 { margin: 0 0 12px; } h1 { font-size: 30px; } h2 { font-size: 21px; }
        .muted { color: #52657a; } .card { background: white; border: 1px solid #dce3eb; border-radius: 12px; padding: 24px; margin-top: 24px; }
        .table-scroll { overflow-x: auto; } table { border-collapse: collapse; width: 100%; text-align: left; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; } th { background: #f5f7fa; font-size: 14px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; background: #edf2f7; white-space: nowrap; }
        .priority-low, .status-done { background: #dcfce7; color: #166534; }
        .priority-medium { background: #fef3c7; color: #854d0e; }
        .priority-high { background: #fee2e2; color: #991b1b; }
        .status-to-do { background: #e2e8f0; color: #334155; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        tr.overdue { background: #fff1f2; }
        tr.overdue td:first-child { border-left: 4px solid #b91c1c; }
        .overdue-label { display: block; color: #b91c1c; font-weight: 700; }
        .language-switch { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-left: auto; }
        .language-switch button { width: auto; margin: 0; padding: 8px 12px; background: white; color: #163653; border: 1px solid #91a1b3; }
        .language-switch button[aria-pressed="true"] { background: #163653; color: white; }
        .activity { padding: 20px 0; border-bottom: 1px solid #dce3eb; } .activity h3 { margin: 0; }
        .charts-grid { display: grid; grid-template-columns: minmax(0, 2fr) repeat(2, minmax(0, 1fr)); gap: 24px; align-items: start; }
        .chart-totals { display: flex; flex-wrap: wrap; gap: 16px; } .chart-totals dd { margin: 4px 0 0; font-weight: 700; }
        .charts-grid > .card { min-width: 0; }
        @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; gap: 0; } }
        .pagination { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 0; background: transparent; }
        summary { cursor: pointer; color: #175c91; font-weight: 700; padding: 8px 0; } .inline-activities { padding: 16px; background: #f8fafc; border-radius: 8px; }
        .chart-container { position: relative; height: 320px; width: 100%; min-width: 0; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin: 18px 0; }
        .summary-card { --kpi-color: #1e40af; --kpi-bg: #eff6ff; position: relative; overflow: hidden; border: 1px solid #dce3eb; border-top: 5px solid var(--kpi-color); border-radius: 14px; padding: 20px; background: linear-gradient(135deg, #ffffff, var(--kpi-bg)); box-shadow: 0 4px 14px rgba(24,40,60,.06); }
        .summary-card::after { content: ''; position: absolute; right: -22px; bottom: -28px; width: 90px; height: 90px; border-radius: 50%; background: var(--kpi-color); opacity: .06; pointer-events: none; }
        .summary-card dt { min-height: 42px; color: #334155; font-size: 14px; font-weight: 750; line-height: 1.4; }
        .summary-card dd { position: relative; margin: 12px 0 0; color: var(--kpi-color); font-size: clamp(36px, 4vw, 48px); font-weight: 800; line-height: 1.1; letter-spacing: -1.5px; font-variant-numeric: tabular-nums; }
        .summary-card p { position: relative; margin: 12px 0 0; color: #475569; font-size: 13px; font-weight: 600; }
        .summary-card dd.kpi-empty { font-size: 22px; letter-spacing: 0; }
        .kpi-open { --kpi-color: #1d4ed8; --kpi-bg: #eff6ff; }
        .kpi-overdue { --kpi-color: #b91c1c; --kpi-bg: #fef2f2; }
        .kpi-due_next_7 { --kpi-color: #92400e; --kpi-bg: #fffbeb; }
        .kpi-done { --kpi-color: #166534; --kpi-bg: #f0fdf4; }
        .kpi-weekly { --kpi-color: #7e22ce; --kpi-bg: #faf5ff; }
        .summary-done { border-top-color: #166534; color: #166534; }
        .summary-in_progress { border-top-color: #1e40af; color: #1e40af; }
        .summary-to_do { border-top-color: #854d0e; color: #854d0e; }
        .summary-overdue { border-top-color: #b91c1c; color: #b91c1c; }
        progress { width: 100%; height: 20px; accent-color: #166534; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #91a1b3; border-radius: 6px; font: inherit; background: white; }
        input:focus, select:focus, textarea:focus, button:focus { outline: 3px solid #89bde5; outline-offset: 2px; }
        button { margin-top: 20px; padding: 11px 22px; border: 0; border-radius: 6px; background: #175c91; color: white; font: inherit; cursor: pointer; }
        .success { background: #e5f5ec; padding: 14px; border: 1px solid #9bcdb1; border-radius: 6px; }
        .errors { background: #fff1f0; padding: 14px; border: 1px solid #db9b99; border-radius: 6px; }
        .error { color: #a32020; margin: 5px 0 0; font-size: 14px; }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } .card { padding: 16px; } main { padding: 0 16px; margin: 24px auto; } header { padding: 20px 16px; } nav, footer { padding-left: 16px; padding-right: 16px; } button { width: 100%; } }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">{{ __('Skip to main content') }}</a>
    <header>
        <div class="header-inner">
            <a class="brand" href="{{ route('tasks.index') }}">{{ __('Internal Task Monitoring') }}</a>
            <p class="header-note">{{ __('Team responsibilities and progress') }}</p>
        </div>
    </header>
    <nav aria-label="{{ __('Main navigation') }}">
        <div class="nav-inner">
            @auth
                @can('view-dashboard')<a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>@endcan
                @if(auth()->user()->role === 'supervisor')<a href="{{ route('users.index') }}">{{ __('Manage users') }}</a>@endif
                <a href="{{ route('tasks.index') }}">{{ __('Task list') }}</a>
                <a href="{{ route('tasks.index') }}#add-task">{{ __('Add task') }}</a>
                <form class="language-switch" method="POST" action="{{ route('logout') }}">@csrf<button>{{ __('Logout') }} — {{ auth()->user()->name }}</button></form>
            @endauth            <form class="language-switch" method="POST" action="{{ route('language.update') }}" aria-label="{{ __('Language') }}">
                @csrf
                <button type="submit" name="locale" value="en" lang="en" aria-pressed="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">English</button>
                <button type="submit" name="locale" value="ms" lang="ms" aria-pressed="{{ app()->getLocale() === 'ms' ? 'true' : 'false' }}">Bahasa Melayu</button>
            </form>
            @error('locale')<p class="error" role="alert">{{ $message }}</p>@enderror
        </div>
    </nav>
    <main id="main-content">@yield('content')</main>
    <footer>
        <div class="footer-inner"><p>&copy; {{ date('Y') }} {{ __('Internal Task Monitoring') }}</p></div>
    </footer>
    @stack('scripts')
</body>
</html>
