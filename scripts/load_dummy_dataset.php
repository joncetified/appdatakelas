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

    $superAdmin = User::query()
        ->where('role', User::ROLE_SUPER_ADMIN)
        ->orderBy('id')
        ->first();

    if (! $superAdmin) {
        throw new RuntimeException('Super admin belum ada.');
    }

    $touchAudit = function (Model $model) use ($superAdmin): void {
        $payload = ['updated_by' => $superAdmin->id];
        if (blank($model->getAttribute('created_by'))) {
            $payload['created_by'] = $superAdmin->id;
        }
        $model->forceFill($payload)->saveQuietly();
    };

    ActivityLog::query()->delete();
    InfrastructureReport::query()->withTrashed()->get()->each(fn (InfrastructureReport $report) => $report->forceDelete());
    Classroom::query()->withTrashed()->get()->each(fn (Classroom $classroom) => $classroom->forceDelete());
    IncomeEntry::query()->withTrashed()->get()->each(fn (IncomeEntry $income) => $income->forceDelete());

    DB::table('sessions')
        ->whereNotNull('user_id')
        ->where('user_id', '!=', $superAdmin->id)
        ->delete();

    User::query()
        ->withTrashed()
        ->where('id', '!=', $superAdmin->id)
        ->get()
        ->each(fn (User $user) => $user->forceDelete());

    $createUser = function (array $payload) use ($touchAudit): User {
        $user = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'email_verified_at' => now(),
            'password' => 'Password123!',
            'role' => $payload['role'],
            'whatsapp_number' => $payload['whatsapp_number'],
        ]);

        $user->syncPermissionsBySlugs(User::defaultPermissionSlugsForRole($user->role));
        $touchAudit($user);

        return $user->fresh();
    };

    // Site Settings
    $siteSetting = SiteSetting::query()->firstOrFail();
    $siteSetting->forceFill([
        'company_name' => 'SPH',
        'address' => 'Jl. Permata No. 123, Kawasan Pendidikan, Indonesia',
        'manager_name' => 'Dr. H. Ahmad Permana, M.Pd.',
        'contact_email' => 'info@permataharapan.sch.id',
        'contact_phone' => '021-87904567',
        'contact_whatsapp' => '081234567890',
    ])->saveQuietly();
    $touchAudit($siteSetting);

    $supportUsers = [
        [
            'name' => 'Admin Operasional',
            'email' => 'admin.operasional@sekolahpermataharapan.test',
            'role' => User::ROLE_ADMIN,
            'whatsapp_number' => '081210000001',
        ],
        [
            'name' => 'Manager Monitoring',
            'email' => 'manager.monitoring@sekolahpermataharapan.test',
            'role' => User::ROLE_MANAGER,
            'whatsapp_number' => '081210000002',
        ],
        [
            'name' => 'Kepala Sekolah',
            'email' => 'kepala.sekolah@sekolahpermataharapan.test',
            'role' => User::ROLE_PRINCIPAL,
            'whatsapp_number' => '081210000003',
        ],
    ];

    foreach ($supportUsers as $user) {
        $createUser($user);
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
                'email' => 'alya.maharani@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000101',
            ],
            'homeroom' => [
                'name' => 'Rini Wulandari',
                'email' => 'rini.wulandari@sekolahpermataharapan.test',
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
                'email' => 'bima.prakoso@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000102',
            ],
            'homeroom' => [
                'name' => 'Dewi Sartika',
                'email' => 'dewi.sartika@sekolahpermataharapan.test',
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
                'email' => 'citra.lestari@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000103',
            ],
            'homeroom' => [
                'name' => 'Fajar Hidayat',
                'email' => 'fajar.hidayat@sekolahpermataharapan.test',
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
                'email' => 'dimas.saputra@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000104',
            ],
            'homeroom' => [
                'name' => 'Gina Melati',
                'email' => 'gina.melati@sekolahpermataharapan.test',
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
                'email' => 'eka.ramadhani@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000105',
            ],
            'homeroom' => [
                'name' => 'Hendra Kusuma',
                'email' => 'hendra.kusuma@sekolahpermataharapan.test',
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
                'email' => 'farah.nabila@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000106',
            ],
            'homeroom' => [
                'name' => 'Intan Pertiwi',
                'email' => 'intan.pertiwi@sekolahpermataharapan.test',
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
                'email' => 'galang.pratama@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000107',
            ],
            'homeroom' => [
                'name' => 'Juni Astuti',
                'email' => 'juni.astuti@sekolahpermataharapan.test',
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
                'email' => 'hafiz.ramadhan@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000108',
            ],
            'homeroom' => [
                'name' => 'Kiki Andriani',
                'email' => 'kiki.andriani@sekolahpermataharapan.test',
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
                'email' => 'intan.lestari@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000109',
            ],
            'homeroom' => [
                'name' => 'Lukman Hakim',
                'email' => 'lukman.hakim@sekolahpermataharapan.test',
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
                'email' => 'jihan.safitri@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000110',
            ],
            'homeroom' => [
                'name' => 'Maya Kartika',
                'email' => 'maya.kartika@sekolahpermataharapan.test',
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
        [
            'classroom' => [
                'name' => 'XI AKL 1 SMK',
                'location' => 'Gedung SMK Lantai 3 Ruang AKL 2',
                'description' => 'Ruang AKL lanjutan untuk praktik spreadsheet akuntansi, laporan keuangan, dan simulasi kas kecil.',
            ],
            'leader' => [
                'name' => 'Kevin Maulana',
                'email' => 'kevin.maulana@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000111',
            ],
            'homeroom' => [
                'name' => 'Nina Marlina',
                'email' => 'nina.marlina@sekolahpermataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000211',
            ],
            'report' => [
                'report_date' => '2026-04-11',
                'student_count' => 30,
                'teacher_count' => 2,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Ruang digunakan untuk simulasi laporan keuangan dan praktik administrasi dokumen.',
                'verification_notes' => 'Seluruh sarana utama tersedia, kipas angin perlu dibersihkan berkala.',
                'verified_at' => '2026-04-12 09:40:00',
            ],
            'items' => [
                ['item_name' => 'Kipas Angin', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Masih normal.'],
                ['item_name' => 'Meja Siswa', 'total_units' => 30, 'damaged_units' => 1, 'notes' => 'Satu meja perlu penguatan kaki.'],
                ['item_name' => 'Layar Proyektor', 'total_units' => 1, 'damaged_units' => 0, 'notes' => 'Layak pakai.'],
            ],
        ],
        [
            'classroom' => [
                'name' => 'Lab Komputer SMK',
                'location' => 'Gedung SMK Lantai 2 Lab Utama',
                'description' => 'Laboratorium komputer SMK yang dipakai bersama jurusan BDP, RPL, dan AKL untuk praktik aplikasi dan ujian.',
            ],
            'leader' => [
                'name' => 'Larasati Putri',
                'email' => 'larasati.putri@sekolahpermataharapan.test',
                'role' => User::ROLE_CLASS_LEADER,
                'whatsapp_number' => '081220000112',
            ],
            'homeroom' => [
                'name' => 'Oki Setiawan',
                'email' => 'oki.setiawan@sekolahpermataharapan.test',
                'role' => User::ROLE_HOMEROOM_TEACHER,
                'whatsapp_number' => '081230000212',
            ],
            'report' => [
                'report_date' => '2026-04-12',
                'student_count' => 36,
                'teacher_count' => 3,
                'status' => InfrastructureReport::STATUS_VERIFIED,
                'notes' => 'Lab utama dipakai untuk praktik coding RPL, spreadsheet AKL, dan pemasaran digital BDP.',
                'verification_notes' => 'Data sudah valid, dua unit CPU perlu perawatan rutin.',
                'verified_at' => '2026-04-13 08:20:00',
            ],
            'items' => [
                ['item_name' => 'Komputer', 'total_units' => 36, 'damaged_units' => 2, 'notes' => 'Dua unit restart sendiri saat dipakai lama.'],
                ['item_name' => 'Kursi Komputer', 'total_units' => 38, 'damaged_units' => 1, 'notes' => 'Satu kursi patah roda.'],
                ['item_name' => 'Switch 24 Port', 'total_units' => 2, 'damaged_units' => 0, 'notes' => 'Semua normal.'],
            ],
        ],
    ];

    foreach ($datasets as $dataset) {
        $leader = $createUser($dataset['leader']);
        $homeroom = $createUser($dataset['homeroom']);

        $classroom = Classroom::query()->create([
            'name' => $dataset['classroom']['name'],
            'location' => $dataset['classroom']['location'],
            'description' => $dataset['classroom']['description'],
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroom->id,
        ]);
        $touchAudit($classroom);

        $reportStatus = $dataset['report']['status'];

        $report = InfrastructureReport::query()->create([
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
        ]);
        $touchAudit($report);

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
        $income = IncomeEntry::query()->create([
            'title' => $incomeEntry['title'],
            'description' => $incomeEntry['description'],
            'amount' => $incomeEntry['amount'],
            'entry_date' => $incomeEntry['entry_date'],
        ]);
        $touchAudit($income);
    }

    $activityRows = [
        [
            'action' => 'dummy.settings.loaded',
            'description' => 'Pengaturan website dummy diperbarui untuk unit SMP dan SMK.',
            'properties' => ['company_name' => 'SPH'],
        ],
        [
            'action' => 'dummy.users.loaded',
            'description' => 'Akun dummy non-super-admin untuk admin, manager, kepala sekolah, ketua kelas, dan wali kelas berhasil dibuat ulang.',
            'properties' => ['users' => User::query()->count()],
        ],
        [
            'action' => 'dummy.classrooms.loaded',
            'description' => 'Data kelas SMP 7-9, kelas jurusan BDP/RPL/AKL, dan lab komputer terbatas berhasil dimuat.',
            'properties' => ['classrooms' => Classroom::query()->count()],
        ],
        [
            'action' => 'dummy.reports.loaded',
            'description' => 'Laporan infrastruktur SMP dan SMK berikut item pendukung berhasil dimuat.',
            'properties' => [
                'reports' => InfrastructureReport::query()->count(),
                'items' => DB::table('infrastructure_report_items')->count(),
            ],
        ],
        [
            'action' => 'dummy.income.loaded',
            'description' => 'Data pemasukan gabungan SMP dan SMK berhasil dimuat.',
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
    echo 'report_items:'.DB::table('infrastructure_report_items')->count().PHP_EOL;
    echo 'income_entries:'.IncomeEntry::query()->count().PHP_EOL;
    echo 'activity_logs:'.ActivityLog::query()->count().PHP_EOL;
});
