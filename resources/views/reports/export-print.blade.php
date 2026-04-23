<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $outputMode === 'pdf' ? 'PDF' : 'Print' }} Laporan Infrastruktur</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --soft: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

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

        .summary {
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

        .meta {
            margin-top: 24px;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 14px 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
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

        .empty {
            margin-top: 24px;
            border: 1px dashed var(--line);
            padding: 24px;
            color: var(--muted);
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
                    <h1>{{ $outputMode === 'pdf' ? 'Dokumen PDF Siap Simpan' : 'Dokumen Print Siap Cetak' }}</h1>
                </div>
            </div>
            <div class="helper">
                @if ($outputMode === 'pdf')
                    Gunakan dialog browser yang muncul untuk memilih <strong>Save as PDF</strong> lalu simpan dokumen.
                @else
                    Gunakan dialog browser yang muncul untuk mencetak dokumen ini langsung ke printer.
                @endif
            </div>
        </section>

        <section class="summary">
            <article class="card">
                <p class="card-label">Total Laporan</p>
                <p class="card-value">{{ $totals['reports'] }}</p>
            </article>
            <article class="card">
                <p class="card-label">Total Item</p>
                <p class="card-value">{{ $totals['items'] }}</p>
            </article>
            <article class="card">
                <p class="card-label">Total Unit</p>
                <p class="card-value">{{ $totals['total_units'] }}</p>
            </article>
            <article class="card">
                <p class="card-label">Unit Rusak</p>
                <p class="card-value">{{ $totals['damaged_units'] }}</p>
            </article>
        </section>

        <section class="meta">
            <div>Diekspor oleh: <strong>{{ $exportedBy->name }}</strong> ({{ $exportedBy->role_label }})</div>
            <div>Waktu export: <strong>{{ $exportedAt }}</strong></div>
            <div>Filter pencarian: <strong>{{ $filters['q'] !== '' ? $filters['q'] : 'Semua data' }}</strong></div>
            <div>Status: <strong>{{ \App\Models\InfrastructureReport::statusOptions()[$filters['status']] ?? 'Semua status' }}</strong></div>
        </section>

        @if ($reports->isEmpty())
            <div class="empty">Tidak ada laporan untuk ditampilkan.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Pelapor</th>
                        <th>Verifikator</th>
                        <th>Siswa</th>
                        <th>Guru</th>
                        <th>Item</th>
                        <th>Total Unit</th>
                        <th>Rusak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $report->report_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $report->classroom->name }}</td>
                            <td>{{ $report->status_label }}</td>
                            <td>{{ $report->reporter->name }}</td>
                            <td>{{ $report->verifier?->name ?? '-' }}</td>
                            <td>{{ $report->student_count }}</td>
                            <td>{{ $report->teacher_count }}</td>
                            <td>{{ $report->items->count() }}</td>
                            <td>{{ $report->total_units }}</td>
                            <td>{{ $report->damaged_units }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
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
