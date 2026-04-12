<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\InfrastructureReportItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

DB::transaction(function (): void {
    $superAdmin = User::query()
        ->where('role', User::ROLE_SUPER_ADMIN)
        ->orderBy('id')
        ->first();

    if (! $superAdmin) {
        throw new RuntimeException('Super admin belum ada. Buat super admin terlebih dahulu.');
    }

    $touchAudit = function (Model $model) use ($superAdmin): void {
        $payload = ['updated_by' => $superAdmin->id];

        if (blank($model->getAttribute('created_by'))) {
            $payload['created_by'] = $superAdmin->id;
        }

        $model->forceFill($payload)->saveQuietly();
    };

    $upsertUser = function (array $payload) use ($superAdmin, $touchAudit): User {
        $user = User::query()->updateOrCreate(
            ['email' => $payload['email']],
            [
                'name' => $payload['name'],
                'email_verified_at' => now(),
                'password' => 'Password123!',
                'role' => $payload['role'],
                'whatsapp_number' => $payload['whatsapp_number'],
            ],
        );

        $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole($user->role));
        $touchAudit($user);

        return $user->fresh();
    };

    $siteSetting = SiteSetting::query()->firstOrFail();
    $siteSetting->forceFill([
        'company_name' => 'SMK Nusantara Digital',
        'address' => 'Jl. Pendidikan Teknologi No. 15, Cibinong, Bogor',
        'manager_name' => 'Rudi Hartono, S.Kom.',
        'contact_email' => 'info@smknusantaradigital.test',
        'contact_phone' => '021-87904567',
        'contact_whatsapp' => '081234567890',
    ])->saveQuietly();
    $touchAudit($siteSetting);

    $supportUsers = [
        [
            'name' => 'Admin Operasional',
            'email' => 'admin.operasional@smknusantaradigital.test',
            'role' => User::ROLE_ADMIN,
            'whatsapp_number' => '081210000001',
        ],
        [
            'name' => 'Manager Monitoring',
            'email' => 'manager.monitoring@smknusantaradigital.test',
            'role' => User::ROLE_MANAGER,
            'whatsapp_number' => '081210000002',
        ],
        [
            'name' => 'Kepala Sekolah',
            'email' => 'kepala.sekolah@smknusantaradigital.test',
            'role' => User::ROLE_PRINCIPAL,
            'whatsapp_number' => '081210000003',
        ],
    ];

    foreach ($supportUsers as $user) {
        $upsertUser($user);
    }

    $datasets = [
        [
            'classroom' => [
                'name' => 'Lab Komputer A',
                'location' => 'Gedung A Lantai 2',
                'description' => 'Laboratorium utama untuk praktik dasar komputer, ujian CBT, dan simulasi sertifikasi.',
            ],
            'leader' => [
                'name' => 'Andi Saputra',
                'email' => 'andi.saputra@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000101',
            ],
            'homeroom' => [
                'name' => 'Siti Rahmawati',
                'email' => 'siti.rahmawati@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000201',
            ],
            'report' => [
                'report_date' => '2026-04-01',
                'student_count' => 32,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Lab dipakai untuk materi pengenalan jaringan dasar dan ujian praktik semester.',
                'verification_notes' => 'Data sesuai hasil pengecekan lapangan, dua unit komputer masuk daftar servis.',
                'verified_at' => '2026-04-02 08:15:00',
            ],
            'items' => [
                ['item_name' => 'Komputer', 'total_units' => 32, 'damaged_units' => 2, 'notes' => 'Dua unit perlu penggantian power supply.'],
                ['item_name' => 'Kursi Komputer', 'total_units' => 34, 'damaged_units' => 1, 'notes' => 'Satu kursi longgar di kaki depan.'],
                ['item_name' => 'Access Point', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Kondisi baik.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Lab Komputer B',
                'location' => 'Gedung A Lantai 2',
                'description' => 'Laboratorium cadangan untuk praktik desain grafis dan pengolahan data.',
            ],
            'leader' => [
                'name' => 'Budi Pratama',
                'email' => 'budi.pratama@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000102',
            ],
            'homeroom' => [
                'name' => 'Agus Setiawan',
                'email' => 'agus.setiawan@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000202',
            ],
            'report' => [
                'report_date' => '2026-04-02',
                'student_count' => 30,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Sebagian komputer digunakan untuk praktik CorelDraw dan spreadsheet.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Komputer', 'total_units' => 30, 'damaged_units' => 1, 'notes' => 'Satu monitor berkedip.'],
                ['item_name' => 'Printer', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Masih layak pakai.'],
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Lampu masih terang.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'X RPL 1',
                'location' => 'Gedung B Ruang 101',
                'description' => 'Ruang kelas untuk siswa kelas X jurusan Rekayasa Perangkat Lunak.',
            ],
            'leader' => [
                'name' => 'Citra Lestari',
                'email' => 'citra.lestari@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000103',
            ],
            'homeroom' => [
                'name' => 'Lina Marlina',
                'email' => 'lina.marlina@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000203',
            ],
            'report' => [
                'report_date' => '2026-04-03',
                'student_count' => 36,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_REVISION_REQUESTED,
                'notes' => 'Inventaris meja sudah diperbarui, jumlah kipas perlu pengecekan ulang.',
                'verification_notes' => 'Jumlah kipas angin belum sesuai kondisi fisik, mohon cek ulang sisi belakang kelas.',
                'verified_at' => '2026-04-04 09:20:00',
            ],
            'items' => [
                ['item_name' => 'Meja Siswa', 'total_units' => 36, 'damaged_units' => 2, 'notes' => 'Dua meja goyang.'],
                ['item_name' => 'Kursi Siswa', 'total_units' => 36, 'damaged_units' => 3, 'notes' => 'Perlu pengencangan baut.'],
                ['item_name' => 'Kipas Angin', 'total_units' => 3, 'damaged_units' => 1, 'notes' => 'Satu unit putaran lemah.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'X TKJ 1',
                'location' => 'Gedung B Ruang 102',
                'description' => 'Ruang belajar untuk praktik dasar jaringan dan sistem komputer.',
            ],
            'leader' => [
                'name' => 'Dimas Ramadhan',
                'email' => 'dimas.ramadhan@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000104',
            ],
            'homeroom' => [
                'name' => 'Rina Handayani',
                'email' => 'rina.handayani@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000204',
            ],
            'report' => [
                'report_date' => '2026-04-04',
                'student_count' => 34,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Perangkat jaringan aktif digunakan untuk simulasi topologi LAN.',
                'verification_notes' => 'Sudah sesuai, kabel patch cord perlu ditata lebih rapi.',
                'verified_at' => '2026-04-05 10:05:00',
            ],
            'items' => [
                ['item_name' => 'Router Mikrotik', 'total_units' => 4, 'damaged_units' => 0, 'notes' => 'Semua aktif.'],
                ['item_name' => 'Switch 24 Port', 'total_units' => 3, 'damaged_units' => 1, 'notes' => 'Satu port uplink bermasalah.'],
                ['item_name' => 'Patch Cord', 'total_units' => 40, 'damaged_units' => 4, 'notes' => 'Sebagian konektor longgar.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'XI RPL 1',
                'location' => 'Gedung B Ruang 201',
                'description' => 'Ruang kelas pengembangan web dan pemrograman lanjutan.',
            ],
            'leader' => [
                'name' => 'Elsa Wulandari',
                'email' => 'elsa.wulandari@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000105',
            ],
            'homeroom' => [
                'name' => 'Yudi Hartono',
                'email' => 'yudi.hartono@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000205',
            ],
            'report' => [
                'report_date' => '2026-04-05',
                'student_count' => 33,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Proyektor utama dipakai untuk presentasi projek aplikasi sekolah.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Normal.'],
                ['item_name' => 'Whiteboard', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Permukaan masih bagus.'],
                ['item_name' => 'Stop Kontak', 'total_units' => 6, 'damaged_units' => 1, 'notes' => 'Satu titik perlu diganti.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'XI TKJ 1',
                'location' => 'Gedung B Ruang 202',
                'description' => 'Ruang kelas praktik administrasi server dan troubleshooting jaringan.',
            ],
            'leader' => [
                'name' => 'Fajar Nugroho',
                'email' => 'fajar.nugroho@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000106',
            ],
            'homeroom' => [
                'name' => 'Nanik Wibowo',
                'email' => 'nanik.wibowo@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000206',
            ],
            'report' => [
                'report_date' => '2026-04-06',
                'student_count' => 31,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Lemari perangkat sudah tertata, butuh pembaruan label kabel.',
                'verification_notes' => 'Sudah sesuai dengan kondisi ruang dan inventaris jurusan.',
                'verified_at' => '2026-04-07 07:45:00',
            ],
            'items' => [
                ['item_name' => 'Rack Server', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Kondisi baik.'],
                ['item_name' => 'UPS', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Daya cadangan normal.'],
                ['item_name' => 'Kabel LAN', 'total_units' => 120, 'damaged_units' => 8, 'notes' => 'Sebagian konektor RJ45 perlu diganti.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'XII RPL 1',
                'location' => 'Gedung C Ruang 301',
                'description' => 'Ruang kelas persiapan PKL dan tugas akhir jurusan RPL.',
            ],
            'leader' => [
                'name' => 'Gita Maharani',
                'email' => 'gita.maharani@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000107',
            ],
            'homeroom' => [
                'name' => 'Beni Saptono',
                'email' => 'beni.saptono@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000207',
            ],
            'report' => [
                'report_date' => '2026-04-07',
                'student_count' => 28,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Ruang dipakai bimbingan final project dan presentasi portofolio.',
                'verification_notes' => 'Inventaris sesuai, hanya perlu pembersihan filter AC berkala.',
                'verified_at' => '2026-04-08 11:30:00',
            ],
            'items' => [
                ['item_name' => 'AC', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Perlu cleaning rutin.'],
                ['item_name' => 'Layar Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Masih layak.'],
                ['item_name' => 'Speaker Aktif', 'total_units' => 2, 'damaged_units' => 1, 'notes' => 'Satu speaker suara pecah.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'XII TKJ 1',
                'location' => 'Gedung C Ruang 302',
                'description' => 'Ruang kelas untuk review ujian kompetensi dan praktik maintenance perangkat.',
            ],
            'leader' => [
                'name' => 'Hendra Kurniawan',
                'email' => 'hendra.kurniawan@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000108',
            ],
            'homeroom' => [
                'name' => 'Tika Puspitasari',
                'email' => 'tika.puspitasari@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000208',
            ],
            'report' => [
                'report_date' => '2026-04-08',
                'student_count' => 29,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_REVISION_REQUESTED,
                'notes' => 'Jumlah toolkit teknisi perlu pencocokan ulang dengan inventaris gudang.',
                'verification_notes' => 'Mohon periksa kembali jumlah toolkit dan kabel tester sebelum disahkan.',
                'verified_at' => '2026-04-09 13:05:00',
            ],
            'items' => [
                ['item_name' => 'Toolkit Teknisi', 'total_units' => 15, 'damaged_units' => 2, 'notes' => 'Beberapa obeng set tidak lengkap.'],
                ['item_name' => 'LAN Tester', 'total_units' => 6, 'damaged_units' => 1, 'notes' => 'LCD satu unit redup.'],
                ['item_name' => 'Crimping Tool', 'total_units' => 10, 'damaged_units' => 1, 'notes' => 'Pegangan satu unit retak.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Perpustakaan Digital',
                'location' => 'Gedung Utama Lantai 1',
                'description' => 'Ruang literasi digital, baca mandiri, dan akses katalog berbasis komputer.',
            ],
            'leader' => [
                'name' => 'Indah Permata',
                'email' => 'indah.permata@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000109',
            ],
            'homeroom' => [
                'name' => 'Dewi Kusuma',
                'email' => 'dewi.kusuma@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000209',
            ],
            'report' => [
                'report_date' => '2026-04-09',
                'student_count' => 26,
                'teacher_count' => 1,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Perpustakaan dipakai untuk layanan peminjaman dan pencarian katalog digital.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Rak Buku', 'total_units' => 20, 'damaged_units' => 1, 'notes' => 'Satu rak miring sedikit.'],
                ['item_name' => 'Komputer OPAC', 'total_units' => 4, 'damaged_units' => 0, 'notes' => 'Semua aktif.'],
                ['item_name' => 'Kursi Baca', 'total_units' => 28, 'damaged_units' => 2, 'notes' => 'Dua unit sandaran longgar.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Ruang Multimedia',
                'location' => 'Gedung Kreatif Lantai 2',
                'description' => 'Studio praktik editing video, fotografi, dan produksi konten sekolah.',
            ],
            'leader' => [
                'name' => 'Joko Santoso',
                'email' => 'joko.santoso@smknusantaradigital.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000110',
            ],
            'homeroom' => [
                'name' => 'Arif Hidayat',
                'email' => 'arif.hidayat@smknusantaradigital.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000210',
            ],
            'report' => [
                'report_date' => '2026-04-10',
                'student_count' => 24,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Studio dipakai untuk produksi video profil sekolah dan dokumentasi kegiatan.',
                'verification_notes' => 'Peralatan utama siap pakai, tripod satu unit perlu servis pengunci.',
                'verified_at' => '2026-04-11 15:10:00',
            ],
            'items' => [
                ['item_name' => 'Kamera DSLR', 'total_units' => 6, 'damaged_units' => 1, 'notes' => 'Satu unit autofocus lambat.'],
                ['item_name' => 'Tripod', 'total_units' => 6, 'damaged_units' => 1, 'notes' => 'Kunci kaki tidak rapat.'],
                ['item_name' => 'PC Editing', 'total_units' => 10, 'damaged_units' => 1, 'notes' => 'Satu unit kipas casing bising.'],
            ],
        ],
    ];

    foreach ($datasets as $dataset) {
        $leader = $upsertUser($dataset['leader']);
        $homeroom = $upsertUser($dataset['homeroom']);

        $classroom = Classroom::query()->updateOrCreate(
            ['name' => $dataset['classroom']['name']],
            [
                'location' => $dataset['classroom']['location'],
                'description' => $dataset['classroom']['description'],
                'leader_id' => $leader->id,
                'homeroom_teacher_id' => $homeroom->id,
            ],
        );
        $touchAudit($classroom);

        $reportStatus = $dataset['report']['status'];

        $report = InfrastructureReport::query()->updateOrCreate(
            [
                'classroom_id' => $classroom->id,
                'report_date' => $dataset['report']['report_date'],
            ],
            [
                'reported_by_id' => $leader->id,
                'verified_by_id' => $reportStatus === InfrastructureReport::STATUS_SUBMITTED ? null : $homeroom->id,
                'student_count' => $dataset['report']['student_count'],
                'teacher_count' => $dataset['report']['teacher_count'],
                'status' => $reportStatus,
                'notes' => $dataset['report']['notes'],
                'verification_notes' => $dataset['report']['verification_notes'],
                'verified_at' => $dataset['report']['verified_at'],
            ],
        );
        $touchAudit($report);

        $itemNames = [];

        foreach ($dataset['items'] as $item) {
            $itemNames[] = $item['item_name'];

            InfrastructureReportItem::query()->updateOrCreate(
                [
                    'infrastructure_report_id' => $report->id,
                    'item_name' => $item['item_name'],
                ],
                [
                    'total_units' => $item['total_units'],
                    'damaged_units' => $item['damaged_units'],
                    'notes' => $item['notes'],
                ],
            );
        }

        InfrastructureReportItem::query()
            ->where('infrastructure_report_id', $report->id)
            ->whereNotIn('item_name', $itemNames)
            ->delete();
    }

    $incomeEntries = [
        ['title' => 'SPP April Gelombang 1', 'description' => 'Pembayaran SPP siswa tahap pertama.', 'amount' => 8500000, 'entry_date' => '2026-04-12'],
        ['title' => 'Donasi Lab Multimedia', 'description' => 'Donasi wali murid untuk penguatan fasilitas studio multimedia.', 'amount' => 3500000, 'entry_date' => '2026-04-12'],
        ['title' => 'SPP April Gelombang 2', 'description' => 'Pembayaran SPP siswa tahap kedua.', 'amount' => 9200000, 'entry_date' => '2026-04-11'],
        ['title' => 'Iuran Praktikum Jaringan', 'description' => 'Iuran praktik untuk modul jaringan kelas TKJ.', 'amount' => 2750000, 'entry_date' => '2026-04-09'],
        ['title' => 'Sewa Aula Seminar', 'description' => 'Pemasukan dari pemakaian aula untuk seminar mitra industri.', 'amount' => 1800000, 'entry_date' => '2026-04-07'],
        ['title' => 'Pembayaran Sertifikasi Siswa', 'description' => 'Biaya sertifikasi internal jurusan RPL dan TKJ.', 'amount' => 4600000, 'entry_date' => '2026-04-04'],
        ['title' => 'Dana Perawatan AC', 'description' => 'Dana pemeliharaan fasilitas pendingin ruangan.', 'amount' => 2200000, 'entry_date' => '2026-03-28'],
        ['title' => 'Donasi Alumni RPL', 'description' => 'Donasi alumni untuk peremajaan perangkat lab.', 'amount' => 5100000, 'entry_date' => '2026-03-18'],
        ['title' => 'Kerja Sama Industri', 'description' => 'Pendapatan dari program kolaborasi dengan perusahaan mitra.', 'amount' => 12500000, 'entry_date' => '2026-02-26'],
        ['title' => 'Bantuan Komite Sekolah', 'description' => 'Bantuan pengadaan sarana penunjang pembelajaran.', 'amount' => 9700000, 'entry_date' => '2026-01-15'],
    ];

    foreach ($incomeEntries as $incomeEntry) {
        $income = IncomeEntry::query()->updateOrCreate(
            [
                'title' => $incomeEntry['title'],
                'entry_date' => $incomeEntry['entry_date'],
            ],
            [
                'description' => $incomeEntry['description'],
                'amount' => $incomeEntry['amount'],
            ],
        );
        $touchAudit($income);
    }

    ActivityLog::query()->where('action', 'like', 'dummy.%')->delete();

    $activityRows = [
        [
            'action' => 'dummy.settings.loaded',
            'description' => 'Pengaturan website dummy diperbarui untuk kebutuhan demo.',
            'properties' => ['company_name' => 'SMK Nusantara Digital'],
        ],
        [
            'action' => 'dummy.users.loaded',
            'description' => 'Akun dummy untuk role admin, manager, kepala sekolah, ketua kelas, dan wali kelas ditambahkan.',
            'properties' => ['users' => User::query()->count()],
        ],
        [
            'action' => 'dummy.classrooms.loaded',
            'description' => 'Data kelas dan ruang dummy berhasil dimasukkan ke sistem.',
            'properties' => ['classrooms' => Classroom::query()->count()],
        ],
        [
            'action' => 'dummy.reports.loaded',
            'description' => 'Laporan infrastruktur dan item dummy berhasil dimasukkan ke sistem.',
            'properties' => [
                'reports' => InfrastructureReport::query()->count(),
                'items' => InfrastructureReportItem::query()->count(),
            ],
        ],
        [
            'action' => 'dummy.income.loaded',
            'description' => 'Data income dummy berhasil dimasukkan ke sistem.',
            'properties' => ['income_entries' => IncomeEntry::query()->count()],
        ],
    ];

    foreach ($activityRows as $row) {
        ActivityLog::query()->create([
            'action' => $row['action'],
            'description' => $row['description'],
            'subject_type' => User::class,
            'subject_id' => $superAdmin->id,
            'causer_id' => $superAdmin->id,
            'properties' => $row['properties'],
        ]);
    }

    echo 'users:'.User::query()->count().PHP_EOL;
    echo 'classrooms:'.Classroom::query()->count().PHP_EOL;
    echo 'reports:'.InfrastructureReport::query()->count().PHP_EOL;
    echo 'report_items:'.InfrastructureReportItem::query()->count().PHP_EOL;
    echo 'income_entries:'.IncomeEntry::query()->count().PHP_EOL;
    echo 'activity_logs:'.ActivityLog::query()->count().PHP_EOL;
});
