<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error {{ $status }} - {{ $siteSettings?->company_name ?? config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#fffaf0_0%,#f8fafc_48%,#eef2ff_100%)]">
    <main class="mx-auto flex min-h-screen max-w-4xl items-center justify-center px-4 py-10">
        <section class="panel w-full max-w-2xl px-8 py-10 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Error {{ $status }}</p>
            <h1 class="mt-4 text-4xl font-semibold text-slate-950">Terjadi masalah pada halaman</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">{{ $message }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ url()->previous() }}" class="btn-secondary">Kembali</a>
                <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
            </div>
        </section>
    </main>
</body>
</html>
