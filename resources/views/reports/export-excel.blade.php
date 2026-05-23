<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Infrastruktur</title>
    <style>
        table {
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }

        td, th {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .no-border { border: 0; }
        .title { font-size: 18pt; font-weight: 700; }
        .subtitle { color: #475569; font-size: 12pt; }
        .meta-label { background: #f8fafc; font-weight: 700; }
        .section-title { background: #0f172a; color: #ffffff; font-weight: 700; text-align: center; }
        .table-head th { background: #f59e0b; color: #111827; font-weight: 700; text-align: center; }
        .number { text-align: right; mso-number-format: "0"; }
        .date { mso-number-format: "\@"; }
        .wrap { white-space: normal; }
    </style>
</head>
<body>
    <table>
        <colgroup>
            <col style="width: 45px;">
            <col style="width: 105px;">
            <col style="width: 170px;">
            <col style="width: 125px;">
            <col style="width: 190px;">
            <col style="width: 190px;">
            <col style="width: 70px;">
            <col style="width: 70px;">
            <col style="width: 90px;">
            <col style="width: 90px;">
            <col style="width: 90px;">
        </colgroup>
        <tr>
            <td colspan="2" rowspan="3" class="no-border" style="height: 96px; text-align: center;">
                <img src="{{ asset($brandLogoPath) }}" alt="{{ $brandName }}" style="width: 92px; height: auto;">
            </td>
            <td colspan="9" class="no-border title">{{ $brandName }}</td>
        </tr>
        <tr>
            <td colspan="9" class="no-border subtitle">Rekap Laporan Infrastruktur Sekolah</td>
        </tr>
        <tr>
            <td colspan="9" class="no-border subtitle">Tanggal export: {{ $exportedAt }}</td>
        </tr>
        <tr>
            <td colspan="11" class="no-border"></td>
        </tr>
        <tr>
            <td colspan="11" class="section-title">Ringkasan Export</td>
        </tr>
        <tr>
            <td colspan="2" class="meta-label">Diekspor oleh</td>
            <td colspan="9">{{ $exportedBy->name }} ({{ $exportedBy->role_label }})</td>
        </tr>
        <tr>
            <td colspan="2" class="meta-label">Filter</td>
            <td colspan="9">Pencarian: {{ $filters['q'] !== '' ? $filters['q'] : 'Semua data' }} | Status: {{ \App\Models\InfrastructureReport::statusOptions()[$filters['status']] ?? 'Semua status' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="meta-label">Total</td>
            <td colspan="9">Laporan: {{ $totals['reports'] }} | Item: {{ $totals['items'] }} | Unit: {{ $totals['total_units'] }} | Unit rusak: {{ $totals['damaged_units'] }}</td>
        </tr>
        <tr>
            <td colspan="11" class="no-border"></td>
        </tr>
        <thead>
            <tr class="table-head">
                <th>No</th>
                <th>Tanggal</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Pelapor</th>
                <th>Verifikator</th>
                <th>Siswa</th>
                <th>Guru</th>
                <th>Total Item</th>
                <th>Total Unit</th>
                <th>Unit Rusak</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reports as $report)
                <tr>
                    <td class="number">{{ $loop->iteration }}</td>
                    <td class="date">{{ $report->report_date->format('d/m/Y') }}</td>
                    <td class="wrap">{{ $report->classroom?->name ?? 'Kelas tidak tersedia' }}</td>
                    <td>{{ $report->status_label }}</td>
                    <td class="wrap">{{ $report->reporter?->name ?? '-' }}</td>
                    <td class="wrap">{{ $report->verifier?->name ?? '-' }}</td>
                    <td class="number">{{ $report->student_count }}</td>
                    <td class="number">{{ $report->teacher_count }}</td>
                    <td class="number">{{ $report->items->count() }}</td>
                    <td class="number">{{ $report->total_units }}</td>
                    <td class="number">{{ $report->damaged_units }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Tidak ada laporan untuk diexport.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
