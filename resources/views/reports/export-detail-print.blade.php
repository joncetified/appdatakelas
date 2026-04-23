<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $outputMode === 'pdf' ? 'PDF' : 'Print' }} Detail Laporan</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --soft: #f8fafc;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eef2f7;
            color: var(--ink);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .page {
            max-width: 1120px;
            margin: 32px auto;
            padding: 32px;
            background: #fff;
            box-shadow: 0 16px 50px rgba(15, 23, 42, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand img {
            width: 84px;
            max-height: 84px;
            object-fit: contain;
            flex: none;
        }

        .eyebrow {
            margin: 0;
            color: #b45309;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3em;
            text-transform: uppercase;
        }

        h1 {
            margin: 12px 0 0;
            font-size: 32px;
            line-height: 1.1;
        }

        .helper {
            max-width: 320px;
            padding: 16px 18px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            font-size: 13px;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .card {
            border: 1px solid var(--line);
            background: var(--soft);
            padding: 16px;
        }

        .card-label {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }

        .card-value {
            margin: 10px 0 0;
            font-size: 28px;
            font-weight: 700;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 24px;
        }

        .meta-card {
            border: 1px solid var(--line);
            padding: 16px;
        }

        .meta-label {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .meta-value {
            margin: 8px 0 0;
            font-size: 15px;
            line-height: 1.7;
        }

        .notes {
            margin-top: 24px;
            display: grid;
            gap: 16px;
        }

        .note-box {
            border: 1px solid var(--line);
            padding: 18px;
            background: var(--soft);
        }

        table {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 10px 12px;
            font-size: 13px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: var(--soft);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .footer {
            margin-top: 20px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                max-width: none;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .helper {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="header">
            <div class="brand">
                <img src="{{ asset($brandLogoPath) }}" alt="{{ $brandName }}">
                <div>
                    <p class="eyebrow">{{ $brandName }}</p>
                    <h1>{{ $report->classroom->name }}</h1>
                </div>
            </div>
            <div class="helper">
                @if ($outputMode === 'pdf')
                    Pilih <strong>Save as PDF</strong> di dialog browser untuk menyimpan dokumen detail laporan ini.
                @else
                    Gunakan dialog browser yang muncul untuk mencetak dokumen detail laporan ini.
                @endif
            </div>
        </section>

        <section class="stats">
            <article class="card">
                <p class="card-label">Siswa</p>
                <p class="card-value">{{ $report->student_count }}</p>
            </article>
            <article class="card">
                <p class="card-label">Guru</p>
                <p class="card-value">{{ $report->teacher_count }}</p>
            </article>
            <article class="card">
                <p class="card-label">Total Unit</p>
                <p class="card-value">{{ $report->total_units }}</p>
            </article>
            <article class="card">
                <p class="card-label">Unit Rusak</p>
                <p class="card-value">{{ $report->damaged_units }}</p>
            </article>
        </section>

        <section class="meta-grid">
            <article class="meta-card">
                <p class="meta-label">Tanggal Pendataan</p>
                <p class="meta-value">{{ $report->report_date->translatedFormat('d F Y') }}</p>
            </article>
            <article class="meta-card">
                <p class="meta-label">Status</p>
                <p class="meta-value">{{ $report->status_label }}</p>
            </article>
            <article class="meta-card">
                <p class="meta-label">Pelapor</p>
                <p class="meta-value">{{ $report->reporter->name }}</p>
            </article>
            <article class="meta-card">
                <p class="meta-label">Diverifikasi Oleh</p>
                <p class="meta-value">{{ $report->verifier?->name ?? '-' }}</p>
            </article>
            <article class="meta-card">
                <p class="meta-label">Ketua Kelas</p>
                <p class="meta-value">{{ $report->classroom->leader?->name ?? '-' }}</p>
            </article>
            <article class="meta-card">
                <p class="meta-label">Wali Kelas</p>
                <p class="meta-value">{{ $report->classroom->homeroomTeacher?->name ?? '-' }}</p>
            </article>
        </section>

        <section class="notes">
            <article class="note-box">
                <p class="meta-label">Catatan Umum</p>
                <p class="meta-value">{{ $report->notes ?: 'Tidak ada catatan umum.' }}</p>
            </article>
            <article class="note-box">
                <p class="meta-label">Catatan Verifikasi</p>
                <p class="meta-value">{{ $report->verification_notes ?: 'Belum ada catatan verifikasi.' }}</p>
            </article>
        </section>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Item</th>
                    <th>Total Unit</th>
                    <th>Unit Baik</th>
                    <th>Unit Rusak</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->total_units }}</td>
                        <td>{{ $item->good_units }}</td>
                        <td>{{ $item->damaged_units }}</td>
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="footer">
            <div>Diekspor oleh: <strong>{{ $exportedBy->name }}</strong> ({{ $exportedBy->role_label }})</div>
            <div>Waktu export: <strong>{{ $exportedAt }}</strong></div>
        </section>
    </main>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    @endif
</body>
</html>
