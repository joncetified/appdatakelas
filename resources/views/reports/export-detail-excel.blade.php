<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Laporan Infrastruktur</title>
</head>
<body>
    <table border="0">
        <tr>
            <td style="width: 120px; vertical-align: middle;">
                <img src="{{ asset($brandLogoPath) }}" alt="{{ $brandName }}" style="max-width: 96px; max-height: 96px;">
            </td>
            <td style="vertical-align: middle;">
                <div><strong>{{ $brandName }}</strong></div>
                <div>Detail Laporan Infrastruktur</div>
            </td>
        </tr>
    </table>

    <br>

    <table border="1">
        <tr>
            <td colspan="2"><strong>Detail Laporan Infrastruktur</strong></td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>{{ $report->classroom->name }}</td>
        </tr>
        <tr>
            <td>Tanggal Pendataan</td>
            <td>{{ $report->report_date->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td>Status</td>
            <td>{{ $report->status_label }}</td>
        </tr>
        <tr>
            <td>Pelapor</td>
            <td>{{ $report->reporter->name }}</td>
        </tr>
        <tr>
            <td>Ketua Kelas</td>
            <td>{{ $report->classroom->leader?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Wali Kelas</td>
            <td>{{ $report->classroom->homeroomTeacher?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Diverifikasi Oleh</td>
            <td>{{ $report->verifier?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Jumlah Siswa</td>
            <td>{{ $report->student_count }}</td>
        </tr>
        <tr>
            <td>Jumlah Guru</td>
            <td>{{ $report->teacher_count }}</td>
        </tr>
        <tr>
            <td>Total Unit</td>
            <td>{{ $report->total_units }}</td>
        </tr>
        <tr>
            <td>Unit Rusak</td>
            <td>{{ $report->damaged_units }}</td>
        </tr>
        <tr>
            <td>Catatan Umum</td>
            <td>{{ $report->notes ?: '-' }}</td>
        </tr>
        <tr>
            <td>Catatan Verifikasi</td>
            <td>{{ $report->verification_notes ?: '-' }}</td>
        </tr>
        <tr>
            <td>Diekspor Oleh</td>
            <td>{{ $exportedBy->name }} ({{ $exportedBy->role_label }})</td>
        </tr>
        <tr>
            <td>Waktu Export</td>
            <td>{{ $exportedAt }}</td>
        </tr>
    </table>

    <br>

    <table border="1">
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
</body>
</html>
