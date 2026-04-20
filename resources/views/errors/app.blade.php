@php($pageBrandName = $brandName ?? 'Sekolah Permata Harapan')
@php($pageBrandLogoPath = $brandLogoPath ?? 'site/permata-harapan-logo.svg')
@php($pageHomeUrl = $homeUrl ?? url('/'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error {{ $status }} - {{ $pageBrandName }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg-start: #fffaf0;
            --bg-mid: #f8fafc;
            --bg-end: #eef2ff;
            --panel: rgba(255, 255, 255, 0.94);
            --border: rgba(226, 232, 240, 0.9);
            --text: #0f172a;
            --muted: #475569;
            --accent: #d97706;
            --button-dark: #0f172a;
            --button-light: #ffffff;
            --shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, var(--bg-start) 0%, var(--bg-mid) 48%, var(--bg-end) 100%);
        }

        main {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        section {
            width: 100%;
            max-width: 760px;
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 40px 32px;
            text-align: center;
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }

        img {
            display: block;
            width: 100%;
            max-width: 280px;
            height: auto;
            margin: 0 auto 18px;
        }

        .eyebrow {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.34em;
            text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 18px 0 0;
            font-size: clamp(32px, 5vw, 44px);
            line-height: 1.08;
        }

        .message {
            margin: 18px auto 0;
            max-width: 560px;
            font-size: 16px;
            line-height: 1.75;
            color: var(--muted);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 32px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 170px;
            padding: 13px 20px;
            border-radius: 999px;
            border: 1px solid transparent;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            opacity: 0.96;
        }

        .button-secondary {
            color: var(--text);
            background: rgba(255, 255, 255, 0.9);
            border-color: var(--border);
        }

        .button-primary {
            color: var(--button-light);
            background: var(--button-dark);
        }
    </style>
</head>
<body>
    <main>
        <section>
            <img src="{{ asset($pageBrandLogoPath) }}" alt="{{ $pageBrandName }}">
            <p class="eyebrow">Error {{ $status }}</p>
            <h1>Terjadi masalah pada halaman</h1>
            <p class="message">{{ $message }}</p>
            <div class="actions">
                <a href="{{ url()->previous() }}" class="button button-secondary">Kembali</a>
                <a href="{{ $pageHomeUrl }}" class="button button-primary">Masuk ke Sistem</a>
            </div>
        </section>
    </main>
</body>
</html>
