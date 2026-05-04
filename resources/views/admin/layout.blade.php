<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Plan Manager')</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --ink: #17202a;
            --muted: #65758b;
            --line: #dfe5ec;
            --line-strong: #cbd5e1;
            --primary: #1f6feb;
            --primary-strong: #174ea6;
            --success-bg: #eaf7ef;
            --success-ink: #166534;
            --warning-bg: #fff7e6;
            --warning-ink: #92400e;
            --danger-bg: #fff1f2;
            --danger-ink: #b42318;
            --shadow: 0 1px 2px rgba(16, 24, 40, .06), 0 10px 30px rgba(16, 24, 40, .04);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-strong); }

        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 248px minmax(0, 1fr); }
        .sidebar {
            background: #111827;
            color: #e5e7eb;
            padding: 20px 14px;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .brand { padding: 0 10px 18px; border-bottom: 1px solid rgba(255,255,255,.12); margin-bottom: 16px; }
        .brand-title { color: #fff; font-size: 16px; font-weight: 700; letter-spacing: 0; }
        .brand-subtitle { color: #9ca3af; font-size: 12px; margin-top: 2px; }
        .nav { display: grid; gap: 4px; }
        .nav a {
            color: #d1d5db;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border-radius: 7px;
        }
        .nav a[aria-current="page"], .nav a:hover { background: rgba(255,255,255,.10); color: #fff; }
        .nav-kicker { color: #8b95a5; font-size: 11px; font-weight: 700; margin: 18px 10px 6px; text-transform: uppercase; letter-spacing: .06em; }

        .main { min-width: 0; }
        .topbar {
            background: rgba(255,255,255,.86);
            border-bottom: 1px solid var(--line);
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 5;
            backdrop-filter: blur(10px);
        }
        .topbar-title { font-weight: 650; }
        .topbar-meta { color: var(--muted); font-size: 13px; }
        main { max-width: 1180px; margin: 0 auto; padding: 28px; }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .page-title { margin: 0; font-size: 26px; line-height: 1.2; letter-spacing: 0; }
        .page-subtitle { color: var(--muted); margin: 6px 0 0; max-width: 760px; }
        .section-title { margin: 0 0 12px; font-size: 16px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .panel, .table-shell {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .panel { padding: 18px; }
        .panel + .panel, .table-shell + .panel, .panel + .table-shell { margin-top: 16px; }
        .stat { padding: 18px; border-left: 4px solid var(--primary); }
        .stat-label { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .stat-value { font-size: 30px; line-height: 1.1; font-weight: 750; margin-top: 8px; }
        .stat-note { color: var(--muted); margin-top: 6px; font-size: 13px; }

        .table-shell { overflow: hidden; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 720px; }
        th {
            background: var(--surface-muted);
            color: #475467;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }
        th, td { padding: 12px 14px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: middle; }
        tbody tr:hover { background: #fbfcfe; }
        tbody tr:last-child td { border-bottom: 0; }

        label { display: grid; gap: 6px; color: #344054; font-weight: 600; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line-strong);
            border-radius: 7px;
            background: #fff;
            color: var(--ink);
            padding: 9px 10px;
            font: inherit;
        }
        input:focus, select:focus, textarea:focus { outline: 2px solid rgba(31,111,235,.18); border-color: var(--primary); }
        textarea { min-height: 170px; font-family: ui-monospace, "SFMono-Regular", Consolas, monospace; font-size: 13px; }
        .field-full { grid-column: 1 / -1; }
        .form-actions { display: flex; align-items: center; gap: 10px; margin-top: 18px; }

        button, .button {
            border: 1px solid transparent;
            background: var(--primary);
            color: #fff;
            padding: 8px 12px;
            border-radius: 7px;
            font-weight: 650;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
        }
        button:hover, .button:hover { background: var(--primary-strong); color: #fff; }
        .button-secondary, .button-muted {
            background: #fff;
            color: #344054;
            border-color: var(--line-strong);
        }
        .button-secondary:hover, .button-muted:hover { background: var(--surface-muted); color: #17202a; }
        .button-danger { background: var(--danger-bg); color: var(--danger-ink); border-color: #fecdd3; }
        .button-danger:hover { background: #ffe4e6; color: var(--danger-ink); }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .actions form { margin: 0; }

        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #eef4ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-success { background: var(--success-bg); color: var(--success-ink); }
        .badge-warning { background: var(--warning-bg); color: var(--warning-ink); }
        .badge-danger { background: var(--danger-bg); color: var(--danger-ink); }
        .muted { color: var(--muted); }
        .mono, code {
            font-family: ui-monospace, "SFMono-Regular", Consolas, monospace;
            font-size: 12px;
            background: #f2f4f7;
            color: #344054;
            border-radius: 5px;
            padding: 2px 5px;
        }
        .status {
            background: var(--success-bg);
            border: 1px solid #abefc6;
            color: var(--success-ink);
            padding: 10px 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .empty-state { padding: 28px; text-align: center; color: var(--muted); }
        .pagination { margin-top: 14px; }

        @media (max-width: 860px) {
            .app-shell { display: block; }
            .sidebar { position: relative; height: auto; }
            .nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .topbar { position: static; padding: 14px 18px; display: block; }
            main { padding: 18px; }
            .page-header { display: block; }
            .page-header .actions { margin-top: 12px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@php
    $routePrefix = config('plan-manager.admin.route_name_prefix', 'plan-manager.');
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Plans', 'route' => 'plans.index'],
        ['label' => 'Rules', 'route' => 'rules.index'],
        ['label' => 'Rule Builder', 'route' => 'rule-builder.index'],
        ['label' => 'Approvals', 'route' => 'approvals.index'],
        ['label' => 'Corrections', 'route' => 'usage-corrections.index'],
    ];
@endphp
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-title">Plan Manager</div>
            <div class="brand-subtitle">Entitlements, usage, trials</div>
        </div>
        <div class="nav-kicker">Workspace</div>
        <nav class="nav" aria-label="Plan Manager navigation">
            @foreach($navItems as $item)
                @php $name = $routePrefix . $item['route']; @endphp
                <a href="{{ route($name) }}" @if(request()->routeIs($name) || request()->routeIs($name . '.*')) aria-current="page" @endif>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>
    <div class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">@yield('topbar', 'Plan Manager Admin')</div>
                <div class="topbar-meta">Local plan configuration layer, not billing operations</div>
            </div>
            <div class="topbar-meta">{{ now()->format('M j, Y') }}</div>
        </div>
        <main>
            @if(session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
