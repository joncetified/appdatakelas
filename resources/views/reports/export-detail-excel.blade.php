<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Laporan Infrastruktur</title>
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
        .section-title { background: #0f172a; color: #ffffff; font-weight: 700; text-align: center; }
        .label { background: #f8fafc; font-weight: 700; }
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
            <col style="width: 220px;">
            <col style="width: 100px;">
            <col style="width: 100px;">
            <col style="width: 100px;">
            <col style="width: 320px;">
        </colgroup>
        <tr>
            <td colspan="1" rowspan="3" class="no-border" style="height: 96px; text-align: center;">
                <img src="{{ asset($brandLogoPath) }}" alt="{{ $brandName }}" style="width: 92px; height: auto;">
            </td>
            <td colspan="5" class="no-border title">{{ $brandName }}</td>
        </tr>
        <tr>
            <td colspan="5" class="no-border subtitle">Detail Laporan Infrastruktur Sekolah</td>
        </tr>
        <tr>
            <td colspan="5" class="no-border subtitle">Tanggal export: {{ $exportedAt }}</td>
        </tr>
        <tr>
            <td colspan="6" class="no-border"></td>
        </tr>
        <tr>
            <td colspan="6" class="section-title">Informasi Laporan</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Kelas</td>
            <td colspan="4" class="wrap">{{ $report->classroom?->name ?? 'Kelas tidak tersedia' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Tanggal Pendataan</td>
            <td colspan="4" class="date">{{ $report->report_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Status</td>
            <td colspan="4">{{ $report->status_label }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Pelapor</td>
            <td colspan="4" class="wrap">{{ $report->reporter?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Ketua Kelas</td>
            <td colspan="4" class="wrap">{{ $report->classroom?->leader?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Wali Kelas</td>
            <td colspan="4" class="wrap">{{ $report->classroom?->homeroomTeacher?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Diverifikasi Oleh</td>
            <td colspan="4" class="wrap">{{ $report->verifier?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Jumlah Siswa / Guru</td>
            <td colspan="4">{{ $report->student_count }} siswa / {{ $report->teacher_count }} guru</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Total Unit / Unit Rusak</td>
            <td colspan="4">{{ $report->total_units }} unit / {{ $report->damaged_units }} rusak</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Catatan Umum</td>
            <td colspan="4" class="wrap">{{ $report->notes ?: '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Catatan Verifikasi</td>
            <td colspan="4" class="wrap">{{ $report->verification_notes ?: '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="label">Diekspor Oleh</td>
            <td colspan="4" class="wrap">{{ $exportedBy->name }} ({{ $exportedBy->role_label }})</td>
        </tr>
        <tr>
            <td colspan="6" class="no-border"></td>
        </tr>
        <thead>
            <tr class="table-head">
                <th>No</th>
                <th>Item</th>
                <th>Total Unit</th>
                <th>Unit Baik</th>
                <th>Unit Rusak</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report->items as $item)
                <tr>
                    <td class="number">{{ $loop->iteration }}</td>
                    <td class="wrap">{{ $item->item_name }}</td>
                    <td class="number">{{ $item->total_units }}</td>
                    <td class="number">{{ $item->good_units }}</td>
                    <td class="number">{{ $item->damaged_units }}</td>
                    <td class="wrap">{{ $item->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Tidak ada item laporan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
