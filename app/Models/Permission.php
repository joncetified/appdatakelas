<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'label',
        'group',
        'description',
    ];

    /**
     * @return array<int, array{slug: string, label: string, group: string, description: string}>
     */
    public static function defaults(): array
    {
        return [
            ['slug' => 'dashboard.view', 'label' => 'Dashboard', 'group' => 'Monitoring', 'description' => 'Membuka dashboard utama.'],
            ['slug' => 'reports.view', 'label' => 'Lihat laporan', 'group' => 'Laporan', 'description' => 'Melihat daftar dan detail laporan.'],
            ['slug' => 'reports.create', 'label' => 'Buat laporan', 'group' => 'Laporan', 'description' => 'Membuat laporan infrastruktur baru.'],
            ['slug' => 'reports.edit', 'label' => 'Edit laporan', 'group' => 'Laporan', 'description' => 'Mengubah laporan yang masih dapat diedit.'],
            ['slug' => 'reports.verify', 'label' => 'Verifikasi laporan', 'group' => 'Laporan', 'description' => 'Memverifikasi atau meminta revisi laporan.'],
            ['slug' => 'reports.delete', 'label' => 'Hapus laporan', 'group' => 'Laporan', 'description' => 'Menghapus laporan secara soft delete.'],
            ['slug' => 'analytics.view', 'label' => 'Analitik & chart', 'group' => 'Monitoring', 'description' => 'Melihat chart laporan dan income.'],
            ['slug' => 'income.view', 'label' => 'Lihat income', 'group' => 'Income', 'description' => 'Melihat ringkasan income.'],
            ['slug' => 'income.manage', 'label' => 'Kelola income', 'group' => 'Income', 'description' => 'Menambah, mengubah, dan menghapus data income.'],
            ['slug' => 'users.manage', 'label' => 'Kelola pengguna', 'group' => 'Master Data', 'description' => 'Mengelola data pengguna.'],
            ['slug' => 'classrooms.manage', 'label' => 'Kelola kelas', 'group' => 'Master Data', 'description' => 'Mengelola data kelas.'],
            ['slug' => 'permissions.manage', 'label' => 'Atur akses halaman', 'group' => 'Keamanan', 'description' => 'Mengatur checklist akses per pengguna.'],
            ['slug' => 'settings.manage', 'label' => 'Pengaturan website', 'group' => 'Keamanan', 'description' => 'Mengubah nama perusahaan, logo, kontak, captcha, dan webhook.'],
            ['slug' => 'activity.view', 'label' => 'Lihat aktivitas', 'group' => 'Keamanan', 'description' => 'Melihat log edit, hapus, restore, import, dan backup.'],
            ['slug' => 'trash.manage', 'label' => 'Restore data terhapus', 'group' => 'Keamanan', 'description' => 'Melihat trash dan restore data soft delete.'],
            ['slug' => 'tools.manage', 'label' => 'Backup & reset database', 'group' => 'Keamanan', 'description' => 'Membuat backup, restore backup, reset database, dan clear cache.'],
            ['slug' => 'exports.manage', 'label' => 'Export & import', 'group' => 'Master Data', 'description' => 'Export dan import users serta items.'],
        ];
    }

    public static function syncDefaults(): void
    {
        foreach (self::defaults() as $permission) {
            self::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'label' => $permission['label'],
                    'group' => $permission['group'],
                    'description' => $permission['description'],
                ],
            );
        }
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')->withTimestamps();
    }
}
