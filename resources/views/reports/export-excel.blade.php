<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Infrastruktur</title>
</head>
<body>
    <table border="0">
        <tr>
            <td style="width: 120px; vertical-align: middle;">
                <img src="{{ asset($brandLogoPath) }}" alt="{{ $brandName }}" style="max-width: 96px; max-height: 96px;">
            </td>
            <td style="vertical-align: middle;">
                <div><strong>{{ $brandName }}</strong></div>
                <div>Rekap Laporan Infrastruktur</div>
            </td>
        </tr>
    </table>

    <br>

    <table border="1">
        <tr>
            <td colspan="11"><strong>Export Laporan Infrastruktur</strong></td>
        </tr>
        <tr>
            <td colspan="11">Diekspor oleh: {{ $exportedBy->name }} ({{ $exportedBy->role_label }})</td>
        </tr>
        <tr>
            <td colspan="11">Waktu export: {{ $exportedAt }}</td>
        </tr>
        <tr>
            <td colspan="11">Filter pencarian: {{ $filters['q'] !== '' ? $filters['q'] : 'Semua data' }} | Status: {{ \App\Models\InfrastructureReport::statusOptions()[$filters['status']] ?? 'Semua status' }}</td>
        </tr>
        <tr>
            <td colspan="11">Total laporan: {{ $totals['reports'] }} | Total item: {{ $totals['items'] }} | Total unit: {{ $totals['total_units'] }} | Unit rusak: {{ $totals['damaged_units'] }}</td>
        </tr>
    </table>

    <br>

    <table border="1">
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
                <th>Total Item</th>
                <th>Total Unit</th>
                <th>Unit Rusak</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reports as $report)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $report->report_date->format('Y-m-d') }}</td>
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
            @empty
                <tr>
                    <td colspan="11">Tidak ada laporan untuk diexport.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
