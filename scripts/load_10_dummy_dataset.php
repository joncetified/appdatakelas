<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\Permission;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

DB::transaction(function (): void {
    Permission::syncDefaults();

    $defaultPassword = 'Password123!';

    $touchAudit = function (Model $model, User $actor): void {
        $payload = ['updated_by' => $actor->id];

        if (blank($model->getAttribute('created_by'))) {
            $payload['created_by'] = $actor->id;
        }

        $model->forceFill($payload)->saveQuietly();
    };

    $upsertUser = function (array $payload, User $actor, string $defaultPassword) use ($touchAudit): User {
        $user = User::query()
            ->withTrashed()
            ->firstOrNew(['email' => $payload['email']]);

        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'email_verified_at' => now(),
            'password' => $defaultPassword,
            'role' => $payload['role'],
            'whatsapp_number' => $payload['whatsapp_number'] ?? null,
            'deleted_at' => null,
        ])->saveQuietly();

        $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole($user->role));
        $touchAudit($user, $actor);

        return $user->fresh();
    };

    $superAdmin = User::query()
        ->withTrashed()
        ->firstOrNew(['email' => 'superadmin@permataharapan.test']);

    $superAdmin->forceFill([
        'name' => 'Super Admin Permata Harapan',
        'email' => 'superadmin@permataharapan.test',
        'email_verified_at' => now(),
        'password' => $defaultPassword,
        'role' => User::ROLE_SUPER_ADMIN,
        'whatsapp_number' => '081210000000',
        'deleted_at' => null,
    ])->saveQuietly();

    $superAdmin->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole(User::ROLE_SUPER_ADMIN));
    $superAdmin->forceFill([
        'created_by' => $superAdmin->id,
        'updated_by' => $superAdmin->id,
    ])->saveQuietly();
    $superAdmin = $superAdmin->fresh();

    $siteSetting = SiteSetting::query()->first() ?? new SiteSetting;
    $siteSetting->forceFill([
        'company_name' => 'SPH',
        'logo_path' => 'site/permata-harapan-logo.svg',
        'address' => 'Jl. Raya Pendidikan No. 8, Cibinong, Bogor',
        'manager_name' => 'Hadi Santoso, S.Pd.',
        'contact_email' => 'info@permataharapan.test',
        'contact_phone' => '021-87904567',
        'contact_whatsapp' => '081234567890',
    ])->saveQuietly();
    $touchAudit($siteSetting, $superAdmin);

    $supportUsers = [
        [
            'name' => 'Admin Operasional',
            'email' => 'admin.operasional@permataharapan.test',
            'role' => User::ROLE_ADMIN,
            'whatsapp_number' => '081210000001',
        ],
        [
            'name' => 'Manager Monitoring',
            'email' => 'manager.monitoring@permataharapan.test',
            'role' => User::ROLE_MANAGER,
            'whatsapp_number' => '081210000002',
        ],
        [
            'name' => 'Kepala Sekolah',
            'email' => 'kepala.sekolah@permataharapan.test',
            'role' => User::ROLE_PRINCIPAL,
            'whatsapp_number' => '081210000003',
        ],
    ];

    foreach ($supportUsers as $supportUser) {
        $upsertUser($supportUser, $superAdmin, $defaultPassword);
    }

    $datasets = [
        [
            'classroom' => [
                'name' => 'Kelas 7A SMP',
                'location' => 'Gedung SMP Lantai 1 Ruang 1',
                'description' => 'Ruang belajar utama untuk siswa kelas 7 SMP dengan fasilitas dasar pembelajaran harian.',
            ],
            'leader' => [
                'name' => 'Alya Maharani',
                'email' => 'alya.maharani@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000101',
            ],
            'homeroom' => [
                'name' => 'Rini Wulandari',
                'email' => 'rini.wulandari@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000201',
            ],
            'report' => [
                'report_date' => '2026-04-01',
                'student_count' => 32,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Ruang dipakai aktif untuk mata pelajaran umum SMP dan kegiatan literasi pagi.',
                'verification_notes' => 'Kondisi kelas sesuai, hanya satu kipas perlu servis ringan.',
                'verified_at' => '2026-04-02 08:10:00',
            ],
            'items' => [
                ['item_name' => 'Meja Siswa', 'total_units' => 32, 'damaged_units' => 1, 'notes' => 'Satu meja goyang ringan.'],
                ['item_name' => 'Kursi Siswa', 'total_units' => 32, 'damaged_units' => 2, 'notes' => 'Dua kursi perlu pengencangan baut.'],
                ['item_name' => 'Kipas Angin', 'total_units' => 2, 'damaged_units' => 1, 'notes' => 'Satu unit berputar lambat.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Kelas 8A SMP',
                'location' => 'Gedung SMP Lantai 1 Ruang 2',
                'description' => 'Ruang kelas 8 SMP untuk pembelajaran reguler dan pendalaman materi semester genap.',
            ],
            'leader' => [
                'name' => 'Bima Prakoso',
                'email' => 'bima.prakoso@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000102',
            ],
            'homeroom' => [
                'name' => 'Dewi Sartika',
                'email' => 'dewi.sartika@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000202',
            ],
            'report' => [
                'report_date' => '2026-04-02',
                'student_count' => 31,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Whiteboard dan proyektor dipakai rutin untuk presentasi materi IPA dan IPS.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Meja Siswa', 'total_units' => 31, 'damaged_units' => 0, 'notes' => 'Kondisi baik.'],
                ['item_name' => 'Whiteboard', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Masih layak pakai.'],
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Normal.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Kelas 9A SMP',
                'location' => 'Gedung SMP Lantai 2 Ruang 1',
                'description' => 'Ruang kelas 9 SMP untuk persiapan asesmen akhir dan bimbingan akademik.',
            ],
            'leader' => [
                'name' => 'Citra Lestari',
                'email' => 'citra.lestari@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000103',
            ],
            'homeroom' => [
                'name' => 'Fajar Hidayat',
                'email' => 'fajar.hidayat@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000203',
            ],
            'report' => [
                'report_date' => '2026-04-03',
                'student_count' => 30,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_REVISION_REQUESTED,
                'notes' => 'Jumlah kursi sudah sesuai, tetapi kondisi stop kontak perlu dicek ulang.',
                'verification_notes' => 'Mohon cek kembali dua titik stop kontak di sisi belakang kelas.',
                'verified_at' => '2026-04-04 09:00:00',
            ],
            'items' => [
                ['item_name' => 'Kursi Siswa', 'total_units' => 30, 'damaged_units' => 1, 'notes' => 'Satu kursi retak di sandaran.'],
                ['item_name' => 'Stop Kontak', 'total_units' => 4, 'damaged_units' => 2, 'notes' => 'Dua titik belum stabil.'],
                ['item_name' => 'Papan Mading', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Kondisi baik.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Lab Komputer SMP 1',
                'location' => 'Gedung SMP Lantai 2 Lab 1',
                'description' => 'Laboratorium komputer SMP untuk pelajaran TIK, asesmen berbasis komputer, dan latihan mengetik.',
            ],
            'leader' => [
                'name' => 'Dimas Saputra',
                'email' => 'dimas.saputra@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000104',
            ],
            'homeroom' => [
                'name' => 'Gina Melati',
                'email' => 'gina.melati@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000204',
            ],
            'report' => [
                'report_date' => '2026-04-04',
                'student_count' => 28,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Lab dipakai bergantian oleh kelas 7 dan 8 untuk praktik aplikasi perkantoran dasar.',
                'verification_notes' => 'Data sudah sesuai, satu monitor perlu penggantian.',
                'verified_at' => '2026-04-05 10:30:00',
            ],
            'items' => [
                ['item_name' => 'Komputer', 'total_units' => 28, 'damaged_units' => 1, 'notes' => 'Satu monitor berkedip.'],
                ['item_name' => 'Kursi Komputer', 'total_units' => 30, 'damaged_units' => 1, 'notes' => 'Satu kursi longgar.'],
                ['item_name' => 'Access Point', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Jaringan stabil.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Lab Komputer SMP 2',
                'location' => 'Gedung SMP Lantai 2 Lab 2',
                'description' => 'Laboratorium cadangan SMP untuk simulasi ujian, praktik presentasi, dan pelatihan dasar coding.',
            ],
            'leader' => [
                'name' => 'Eka Ramadhani',
                'email' => 'eka.ramadhani@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000105',
            ],
            'homeroom' => [
                'name' => 'Hendra Kusuma',
                'email' => 'hendra.kusuma@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000205',
            ],
            'report' => [
                'report_date' => '2026-04-05',
                'student_count' => 26,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Lab digunakan untuk latihan presentasi dan simulasi asesmen digital kelas 9.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Komputer', 'total_units' => 26, 'damaged_units' => 2, 'notes' => 'Dua unit lambat saat booting.'],
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Normal.'],
                ['item_name' => 'Printer', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Masih baik.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'X BDP 1 SMK',
                'location' => 'Gedung SMK Lantai 1 Ruang BDP 1',
                'description' => 'Ruang kelas jurusan Bisnis Daring dan Pemasaran untuk dasar pemasaran dan komunikasi bisnis.',
            ],
            'leader' => [
                'name' => 'Farah Nabila',
                'email' => 'farah.nabila@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000106',
            ],
            'homeroom' => [
                'name' => 'Intan Pertiwi',
                'email' => 'intan.pertiwi@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000206',
            ],
            'report' => [
                'report_date' => '2026-04-06',
                'student_count' => 33,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Ruang dipakai untuk praktik display produk dan pengantar pemasaran digital.',
                'verification_notes' => 'Fasilitas utama siap pakai, etalase perlu pembersihan rutin.',
                'verified_at' => '2026-04-07 08:25:00',
            ],
            'items' => [
                ['item_name' => 'Etalase Produk', 'total_units' => 3, 'damaged_units' => 0, 'notes' => 'Kondisi baik.'],
                ['item_name' => 'Meja Diskusi', 'total_units' => 8, 'damaged_units' => 1, 'notes' => 'Satu meja kurang stabil.'],
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Normal.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'XI BDP 1 SMK',
                'location' => 'Gedung SMK Lantai 1 Ruang BDP 2',
                'description' => 'Ruang lanjutan jurusan BDP untuk praktik administrasi bisnis, promosi, dan pelayanan pelanggan.',
            ],
            'leader' => [
                'name' => 'Galang Pratama',
                'email' => 'galang.pratama@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000107',
            ],
            'homeroom' => [
                'name' => 'Juni Astuti',
                'email' => 'juni.astuti@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000207',
            ],
            'report' => [
                'report_date' => '2026-04-07',
                'student_count' => 31,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Ruang aktif dipakai praktik layanan pelanggan dan administrasi transaksi sederhana.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Rak Arsip', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Masih rapi dan kuat.'],
                ['item_name' => 'Kursi Siswa', 'total_units' => 31, 'damaged_units' => 2, 'notes' => 'Dua kursi perlu penggantian alas duduk.'],
                ['item_name' => 'LCD TV Display', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Digunakan untuk simulasi promosi produk.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'X RPL 1 SMK',
                'location' => 'Gedung SMK Lantai 2 Ruang RPL 1',
                'description' => 'Ruang kelas Rekayasa Perangkat Lunak untuk dasar pemrograman, logika, dan desain antarmuka.',
            ],
            'leader' => [
                'name' => 'Hafiz Ramadhan',
                'email' => 'hafiz.ramadhan@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000108',
            ],
            'homeroom' => [
                'name' => 'Kiki Andriani',
                'email' => 'kiki.andriani@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000208',
            ],
            'report' => [
                'report_date' => '2026-04-08',
                'student_count' => 34,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_REVISION_REQUESTED,
                'notes' => 'Jumlah stop kontak dan kondisi PC pendukung presentasi perlu dicocokkan kembali.',
                'verification_notes' => 'Mohon verifikasi ulang jumlah stop kontak aktif di sisi kiri ruang kelas.',
                'verified_at' => '2026-04-09 11:00:00',
            ],
            'items' => [
                ['item_name' => 'PC Guru', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Normal.'],
                ['item_name' => 'Stop Kontak', 'total_units' => 6, 'damaged_units' => 1, 'notes' => 'Satu titik longgar.'],
                ['item_name' => 'Whiteboard', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Masih baik.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'XI RPL 1 SMK',
                'location' => 'Gedung SMK Lantai 2 Ruang RPL 2',
                'description' => 'Ruang RPL untuk pemrograman web, basis data, dan pengembangan proyek aplikasi.',
            ],
            'leader' => [
                'name' => 'Intan Lestari',
                'email' => 'intan.lestari@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000109',
            ],
            'homeroom' => [
                'name' => 'Lukman Hakim',
                'email' => 'lukman.hakim@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000209',
            ],
            'report' => [
                'report_date' => '2026-04-09',
                'student_count' => 32,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Ruang dipakai presentasi proyek sistem informasi sekolah dan review database.',
                'verification_notes' => 'Semua item sesuai, satu speaker perlu servis ringan.',
                'verified_at' => '2026-04-10 14:15:00',
            ],
            'items' => [
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Masih terang.'],
                ['item_name' => 'Speaker Aktif', 'total_units' => 2, 'damaged_units' => 1, 'notes' => 'Satu unit suara pecah.'],
                ['item_name' => 'Meja Siswa', 'total_units' => 32, 'damaged_units' => 1, 'notes' => 'Satu meja retak di sisi sudut.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'X AKL 1 SMK',
                'location' => 'Gedung SMK Lantai 3 Ruang AKL 1',
                'description' => 'Ruang Akuntansi dan Keuangan Lembaga untuk praktik pencatatan transaksi dan administrasi keuangan dasar.',
            ],
            'leader' => [
                'name' => 'Jihan Safitri',
                'email' => 'jihan.safitri@permataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000110',
            ],
            'homeroom' => [
                'name' => 'Maya Kartika',
                'email' => 'maya.kartika@permataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000210',
            ],
            'report' => [
                'report_date' => '2026-04-10',
                'student_count' => 33,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'notes' => 'Kelas digunakan untuk praktik jurnal umum, buku besar, dan pengantar aplikasi akuntansi.',
                'verification_notes' => null,
                'verified_at' => null,
            ],
            'items' => [
                ['item_name' => 'Kalkulator', 'total_units' => 33, 'damaged_units' => 2, 'notes' => 'Dua unit tombol tidak responsif.'],
                ['item_name' => 'Lemari Arsip', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Kondisi baik.'],
                ['item_name' => 'Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Normal.'],
            ],
        ],
    ];

    foreach ($datasets as $dataset) {
        $leader = $upsertUser($dataset['leader'], $superAdmin, $defaultPassword);
        $homeroom = $upsertUser($dataset['homeroom'], $superAdmin, $defaultPassword);

        $classroom = Classroom::query()
            ->withTrashed()
            ->firstOrNew(['name' => $dataset['classroom']['name']]);

        $classroom->forceFill([
            'name' => $dataset['classroom']['name'],
            'location' => $dataset['classroom']['location'],
            'description' => $dataset['classroom']['description'],
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroom->id,
            'deleted_at' => null,
        ])->saveQuietly();
        $touchAudit($classroom, $superAdmin);

        $reportStatus = $dataset['report']['status'];

        $report = InfrastructureReport::query()
            ->withTrashed()
            ->firstOrNew([
                'classroom_id' => $classroom->id,
                'report_date' => $dataset['report']['report_date'],
            ]);

        $report->forceFill([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'verified_by_id' => $reportStatus === InfrastructureReport::STATUS_SUBMITTED ? null : $homeroom->id,
            'report_date' => $dataset['report']['report_date'],
            'student_count' => $dataset['report']['student_count'],
            'teacher_count' => $dataset['report']['teacher_count'],
            'status' => $reportStatus,
            'notes' => $dataset['report']['notes'],
            'verification_notes' => $dataset['report']['verification_notes'],
            'verified_at' => $dataset['report']['verified_at'],
            'deleted_at' => null,
        ])->saveQuietly();
        $touchAudit($report, $superAdmin);

        $report->items()->delete();
        $report->items()->createMany($dataset['items']);
    }

    $incomeEntries = [
        ['title' => 'SPP SMP April Gelombang 1', 'description' => 'Pembayaran SPP siswa SMP tahap pertama.', 'amount' => 6400000, 'entry_date' => '2026-04-12'],
        ['title' => 'SPP SMK April Gelombang 1', 'description' => 'Pembayaran SPP siswa SMK tahap pertama.', 'amount' => 9150000, 'entry_date' => '2026-04-12'],
        ['title' => 'Daftar Ulang SMP', 'description' => 'Pembayaran daftar ulang siswa SMP tahun ajaran baru.', 'amount' => 4800000, 'entry_date' => '2026-04-10'],
        ['title' => 'Daftar Ulang SMK', 'description' => 'Pembayaran daftar ulang siswa SMK tahun ajaran baru.', 'amount' => 7350000, 'entry_date' => '2026-04-09'],
        ['title' => 'Iuran Praktik BDP', 'description' => 'Iuran praktik jurusan Bisnis Daring dan Pemasaran.', 'amount' => 2450000, 'entry_date' => '2026-04-08'],
        ['title' => 'Iuran Praktik RPL', 'description' => 'Iuran praktik jurusan Rekayasa Perangkat Lunak.', 'amount' => 3100000, 'entry_date' => '2026-04-07'],
        ['title' => 'Iuran Praktik AKL', 'description' => 'Iuran praktik jurusan Akuntansi dan Keuangan Lembaga.', 'amount' => 2650000, 'entry_date' => '2026-04-05'],
        ['title' => 'Donasi Lab Komputer', 'description' => 'Donasi wali murid untuk perawatan laboratorium komputer SMP dan SMK.', 'amount' => 5200000, 'entry_date' => '2026-03-28'],
        ['title' => 'Bantuan Komite Pendidikan', 'description' => 'Bantuan komite untuk penunjang sarana pembelajaran gabungan SMP dan SMK.', 'amount' => 8900000, 'entry_date' => '2026-03-18'],
        ['title' => 'Sewa Aula Kegiatan', 'description' => 'Pemasukan dari pemakaian aula sekolah untuk kegiatan luar.', 'amount' => 1750000, 'entry_date' => '2026-03-05'],
    ];

    foreach ($incomeEntries as $incomeEntry) {
        $income = IncomeEntry::query()
            ->withTrashed()
            ->firstOrNew([
                'title' => $incomeEntry['title'],
                'entry_date' => $incomeEntry['entry_date'],
            ]);

        $income->forceFill([
            'title' => $incomeEntry['title'],
            'description' => $incomeEntry['description'],
            'amount' => $incomeEntry['amount'],
            'entry_date' => $incomeEntry['entry_date'],
            'deleted_at' => null,
        ])->saveQuietly();
        $touchAudit($income, $superAdmin);
    }

    ActivityLog::query()->create([
        'action' => 'dummy.dataset.loaded',
        'description' => '10 data dummy realistis berhasil dimuat ulang ke database tanpa seeder.',
        'subject_type' => User::class,
        'subject_id' => $superAdmin->id,
        'causer_id' => $superAdmin->id,
        'properties' => [
            'users' => User::query()->count(),
            'classrooms' => Classroom::query()->count(),
            'reports' => InfrastructureReport::query()->count(),
            'report_items' => DB::table('infrastructure_report_items')->count(),
            'income_entries' => IncomeEntry::query()->count(),
        ],
    ]);

    echo 'super_admin_email:'.$superAdmin->email.PHP_EOL;
    echo 'default_password:'.$defaultPassword.PHP_EOL;
    echo 'users:'.User::query()->count().PHP_EOL;
    echo 'classrooms:'.Classroom::query()->count().PHP_EOL;
    echo 'reports:'.InfrastructureReport::query()->count().PHP_EOL;
    echo 'report_items:'.DB::table('infrastructure_report_items')->count().PHP_EOL;
    echo 'income_entries:'.IncomeEntry::query()->count().PHP_EOL;
    echo 'activity_logs:'.ActivityLog::query()->count().PHP_EOL;
});
