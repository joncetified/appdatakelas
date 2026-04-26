<?php

namespace App\Services;

use App\Models\InfrastructureReport;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\RequestException;
use Throwable;

class GroqSupportChatService
{
    /**
     * @return array{answer: string, suggestions: array<int, string>, actions: array<int, array{label: string, url: string}>, response_id: ?string}
     */
    public function reply(
        string $message,
        ?User $user = null,
        ?string $currentRoute = null,
        ?string $previousResponseId = null,
        ?string $safetyIdentifier = null,
    ): array {
        $message = trim($message);

        if ($message === '') {
            return $this->response(
                answer: 'Silakan tulis pertanyaan singkat seperti "cara buat laporan" atau "menu saya".',
                suggestions: $this->defaultSuggestions($user),
            );
        }

        $apiKey = (string) (config('services.groq.key') ?: config('services.groq.api_key'));
        $settings = SiteSetting::query()->first();

        if ($apiKey === '') {
            return $this->localReply($message, $user, $currentRoute, $settings);
        }

        $payload = [
            'model' => config('services.groq.model', 'openai/gpt-oss-20b'),
            'instructions' => $this->buildInstructions($user, $currentRoute, $settings),
            'input' => [[
                'role' => 'user',
                'content' => $message,
            ]],
            'reasoning' => [
                'effort' => config('services.groq.reasoning_effort', 'low'),
            ],
            'max_output_tokens' => (int) config('services.groq.max_output_tokens', 500),
        ];

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withToken($apiKey)
                ->timeout((int) config('services.groq.timeout', 30))
                ->post(config('services.groq.url', 'https://api.groq.com/openai/v1/responses'), $payload)
                ->throw()
                ->json();
        } catch (Throwable $error) {
            report($error);

            return $this->remoteFailureReply($error, $message, $user, $currentRoute, $settings);
        }

        $answer = $this->extractOutputText($response);

        if ($answer === '') {
            $answer = 'Maaf, saya belum menangkap maksud pertanyaan itu. Coba tulis lagi dengan sedikit lebih spesifik.';
        }

        return $this->response(
            answer: $answer,
            suggestions: $this->followUpSuggestions($message, $user),
            actions: $this->actionsForMessage($message, $user, $settings),
            responseId: $response['id'] ?? null,
        );
    }

    private function remoteFailureReply(
        Throwable $error,
        string $message,
        ?User $user,
        ?string $currentRoute,
        ?SiteSetting $settings,
    ): array {
        $notice = 'Asisten PH sedang mengalami gangguan sementara. Coba lagi sebentar atau hubungi admin bila masalahnya masih berlanjut.';

        if ($error instanceof RequestException && $error->response?->status() === 429) {
            $notice = 'Asisten PH sudah terhubung ke Groq, tetapi kuota atau billing API sedang habis. Untuk sementara saya jawab dengan mode bantuan dasar.';
        }

        $fallback = $this->localReply($message, $user, $currentRoute, $settings);
        $fallback['answer'] = $notice."\n\n".$fallback['answer'];
        $fallback['response_id'] = null;

        return $fallback;
    }

    private function localReply(string $message, ?User $user, ?string $currentRoute, ?SiteSetting $settings): array
    {
        $normalized = $this->normalize($message);

        if ($this->matches($normalized, ['halo', 'hai', 'hello', 'pagi', 'siang', 'sore', 'malam', 'assalamualaikum'])) {
            $name = $user ? $this->firstName($user->name) : null;
            $greeting = $name ? "Halo {$name}." : 'Halo.';

            return $this->response(
                answer: $greeting.' Saya Asisten PH. Saat ini saya tetap bisa bantu untuk menu, login, laporan, status data, dan kontak admin.',
                suggestions: $this->defaultSuggestions($user),
                actions: array_slice(array_merge($this->menuItemsFor($user), $this->contactActions($settings, $user)), 0, 4),
            );
        }

        if ($this->matches($normalized, ['menu saya', 'menu', 'fitur', 'halaman'])) {
            $menus = $this->menuItemsFor($user);

            if ($menus === []) {
                return $this->response(
                    answer: 'Saat ini Anda belum login, jadi menu utama yang tersedia adalah Login, Daftar, dan Lupa Password.',
                    suggestions: $this->defaultSuggestions($user),
                    actions: $menus,
                );
            }

            $labels = array_map(fn (array $item): string => $item['label'], $menus);

            return $this->response(
                answer: 'Menu yang tersedia untuk akun Anda: '.$this->naturalList($labels).'.',
                suggestions: $this->defaultSuggestions($user),
                actions: array_slice($menus, 0, 4),
            );
        }

        if ($this->matches($normalized, ['status laporan saya', 'status laporan', 'laporan saya'])) {
            $summary = $this->reportSummary($user);

            return $this->response(
                answer: $summary
                    ? 'Ringkasan laporan Anda saat ini: '.$summary
                    : 'Saya belum bisa menampilkan ringkasan laporan untuk akun ini, biasanya karena akun belum punya akses melihat laporan.',
                suggestions: ['Buka laporan', 'Cara buat laporan', 'Menu saya'],
                actions: $this->actionsForMessage('laporan', $user, $settings),
            );
        }

        if ($this->matches($normalized, ['cara buat laporan', 'buat laporan'])) {
            $answer = match (true) {
                ! $user => 'Untuk membuat laporan, login dulu ke akun Anda. Setelah masuk, buka menu Laporan Infrastruktur lalu pilih Buat Laporan bila role Anda mengizinkan.',
                ! $user->hasPermission('reports.view') => 'Akun ini belum punya akses ke modul laporan. Hubungi admin bila seharusnya Anda bisa membuka laporan.',
                ! $user->hasPermission('reports.create') => 'Akun ini bisa melihat laporan, tetapi tidak punya izin membuat laporan baru.',
                default => 'Untuk membuat laporan, buka menu Laporan Infrastruktur lalu pilih Buat Laporan. Isi tanggal, jumlah siswa, jumlah guru, dan item infrastruktur, lalu kirim.',
            };

            return $this->response(
                answer: $answer,
                suggestions: ['Status laporan saya', 'Menu saya', 'Kontak admin'],
                actions: $this->actionsForMessage('laporan', $user, $settings),
            );
        }

        if ($this->matches($normalized, ['cara login', 'login'])) {
            return $this->response(
                answer: 'Untuk login, buka halaman Login lalu masukkan username berupa nama lengkap dan password akun Anda. Setelah itu masukkan kode OTP yang dikirim ke email. Jika lupa password, gunakan menu Lupa Password atau hubungi admin sekolah.',
                suggestions: ['Lupa password', 'Kontak admin', 'Menu saya'],
                actions: $this->menuItemsFor(null),
            );
        }

        if ($this->matches($normalized, ['lupa password', 'reset password', 'password'])) {
            return $this->response(
                answer: 'Jika lupa password, buka halaman Lupa Password lalu kirim permintaan reset. Jika jalur reset tidak tersedia, hubungi admin melalui email atau WhatsApp sekolah.',
                suggestions: ['Cara login', 'Kontak admin'],
                actions: array_slice(array_merge([$this->action('Lupa Password', route('password.request'))], $this->contactActions($settings, $user)), 0, 4),
            );
        }

        if ($this->matches($normalized, ['kontak admin', 'kontak', 'admin', 'whatsapp', 'email'])) {
            $contact = $this->contactSummary($settings);

            return $this->response(
                answer: $contact
                    ? 'Kontak yang tersedia saat ini: '.$contact.'.'
                    : 'Kontak admin belum diisi di pengaturan website.',
                suggestions: ['Cara login', 'Menu saya', 'Status laporan saya'],
                actions: $this->contactActions($settings, $user),
            );
        }

        if ($this->matches($normalized, ['dashboard', 'income', 'pemasukan', 'verifikasi', 'kelas'])) {
            return $this->response(
                answer: 'Saya masih berjalan di mode bantuan dasar. Saya bisa bantu jelaskan dashboard, laporan, income, kelas, dan kontak admin sesuai menu yang tersedia untuk akun Anda.',
                suggestions: $this->followUpSuggestions($message, $user),
                actions: $this->actionsForMessage($message, $user, $settings),
            );
        }

        $routeSummary = $this->routeSummary($currentRoute);

        return $this->response(
            answer: $routeSummary
                ? 'Saya bisa bantu jelaskan halaman ini juga. Saat ini Anda sedang membuka '.$routeSummary
                : 'Saya siap bantu untuk pertanyaan dasar seperti menu, login, laporan, status laporan, dan kontak admin.',
            suggestions: $this->defaultSuggestions($user),
            actions: array_slice(array_merge($this->actionsForMessage($message, $user, $settings), $this->contactActions($settings, $user)), 0, 4),
        );
    }

    private function buildInstructions(?User $user, ?string $currentRoute, ?SiteSetting $settings): string
    {
        return implode("\n", [
            'Anda adalah Asisten PH, asisten digital di aplikasi web pendataan infrastruktur sekolah.',
            'Saat memperkenalkan diri, gunakan nama Asisten PH. Jangan menyebut vendor model, nama API, atau istilah teknis internal kecuali user memang menanyakannya secara langsung.',
            'Jawab selalu dalam Bahasa Indonesia yang natural, sopan, ringkas, jelas, dan terasa seperti dibantu manusia.',
            'Prioritaskan konteks aplikasi yang diberikan. Jangan mengarang fakta di luar konteks.',
            'Jika informasi tidak ada di konteks, katakan dengan jujur bahwa data itu tidak tersedia di aplikasi.',
            'Jika user menanyakan langkah penggunaan aplikasi, jelaskan menu yang perlu dibuka dengan nama yang ada di konteks.',
            'Jangan meminta password, OTP, atau data sensitif.',
            '',
            'KONTEKS APLIKASI',
            $this->buildContextSummary($user, $currentRoute, $settings),
        ]);
    }

    private function buildContextSummary(?User $user, ?string $currentRoute, ?SiteSetting $settings): string
    {
        $brandName = trim((string) ($settings?->company_name ?: config('app.name', 'AppDataKelas')));
        $routeSummary = $this->routeSummary($currentRoute);
        $classroomSummary = $this->classroomSummary($user);
        $reportSummary = $this->reportSummary($user);
        $contactSummary = $this->contactSummary($settings);
        $menus = $this->menuItemsFor($user);
        $menuLabels = array_map(fn (array $item): string => $item['label'], $menus);

        return implode("\n", array_filter([
            "- Nama aplikasi: {$brandName}",
            '- Fungsi utama: pendataan laporan infrastruktur sekolah, verifikasi wali kelas, monitoring dashboard, dan income.',
            $user
                ? "- User aktif: {$user->name} ({$user->role_label})"
                : '- User aktif: belum login (guest)',
            $menuLabels !== []
                ? '- Menu yang tersedia: '.$this->naturalList($menuLabels)
                : '- Menu yang tersedia: belum ada menu khusus karena user guest.',
            $routeSummary ? "- Halaman aktif: {$routeSummary}" : null,
            $classroomSummary ? "- Kaitan kelas: {$classroomSummary}" : null,
            $reportSummary ? "- Ringkasan laporan: {$reportSummary}" : null,
            $contactSummary ? "- Kontak bantuan: {$contactSummary}" : null,
        ]));
    }

    private function routeSummary(?string $currentRoute): ?string
    {
        return match (true) {
            $currentRoute === 'dashboard' => 'Dashboard dengan statistik, laporan terbaru, dan grafik sesuai role user.',
            $currentRoute === 'chat.index' => 'Halaman chat AI untuk tanya jawab bantuan penggunaan aplikasi.',
            $currentRoute === 'reports.index' => 'Daftar laporan infrastruktur.',
            $currentRoute === 'reports.create' => 'Form membuat laporan baru.',
            $currentRoute === 'reports.edit' => 'Form mengubah laporan yang masih bisa diedit.',
            $currentRoute === 'reports.show' => 'Detail laporan, item, status, dan catatan verifikasi.',
            $currentRoute === 'income.index' => 'Daftar dan ringkasan income.',
            $currentRoute === 'login' => 'Halaman login.',
            Str::startsWith((string) $currentRoute, 'admin.users.') => 'Kelola pengguna.',
            Str::startsWith((string) $currentRoute, 'admin.classrooms.') => 'Kelola kelas.',
            Str::startsWith((string) $currentRoute, 'admin.permissions.') => 'Atur hak akses.',
            Str::startsWith((string) $currentRoute, 'admin.settings.') => 'Pengaturan website dan kontak.',
            Str::startsWith((string) $currentRoute, 'admin.activity.') => 'Log aktivitas.',
            Str::startsWith((string) $currentRoute, 'admin.trash.') => 'Trash dan restore data.',
            Str::startsWith((string) $currentRoute, 'admin.tools.') => 'Backup, restore backup, clear cache, reset database, export, dan import.',
            default => null,
        };
    }

    private function classroomSummary(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->isClassLeader()) {
            $classroom = $user->ledClassroom()->first();

            return $classroom ? "Ketua kelas untuk {$classroom->name}." : 'Ketua kelas tetapi belum terhubung ke data kelas.';
        }

        if ($user->isHomeroomTeacher()) {
            $classrooms = $user->homeroomClassrooms()->orderBy('name')->pluck('name')->all();

            return $classrooms !== []
                ? 'Wali kelas untuk '.$this->naturalList($classrooms).'.'
                : 'Wali kelas tetapi belum terhubung ke data kelas.';
        }

        return null;
    }

    private function reportSummary(?User $user): ?string
    {
        if (! $user || ! $user->hasPermission('reports.view')) {
            return null;
        }

        $query = InfrastructureReport::query()->visibleTo($user);
        $submitted = (clone $query)->where('status', InfrastructureReport::STATUS_SUBMITTED)->count();
        $revision = (clone $query)->where('status', InfrastructureReport::STATUS_REVISION_REQUESTED)->count();
        $verified = (clone $query)->where('status', InfrastructureReport::STATUS_VERIFIED)->count();

        return "{$submitted} menunggu verifikasi, {$revision} perlu revisi, {$verified} terverifikasi.";
    }

    private function contactSummary(?SiteSetting $settings): ?string
    {
        $parts = array_filter([
            $settings?->manager_name ? "Manager {$settings->manager_name}" : null,
            $settings?->contact_email ? "email {$settings->contact_email}" : null,
            $settings?->contact_phone ? "telepon {$settings->contact_phone}" : null,
            $settings?->contact_whatsapp ? "WhatsApp {$settings->contact_whatsapp}" : null,
        ]);

        return $parts === [] ? null : $this->naturalList($parts);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractOutputText(array $payload): string
    {
        $chunks = [];

        foreach (($payload['output'] ?? []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && filled($content['text'] ?? null)) {
                    $chunks[] = trim((string) $content['text']);
                }

                if (($content['type'] ?? null) === 'refusal' && filled($content['refusal'] ?? null)) {
                    $chunks[] = trim((string) $content['refusal']);
                }
            }
        }

        return trim(implode("\n\n", array_filter($chunks)));
    }

    /**
     * @return array<int, string>
     */
    private function followUpSuggestions(string $message, ?User $user): array
    {
        $normalized = $this->normalize($message);

        if ($this->matches($normalized, ['laporan', 'verifikasi', 'revisi'])) {
            return ['Status laporan saya', 'Cara buat laporan', 'Menu saya'];
        }

        if ($this->matches($normalized, ['income', 'pemasukan'])) {
            return ['Menu saya', 'Dashboard', 'Kontak admin'];
        }

        if ($this->matches($normalized, ['kontak', 'admin', 'whatsapp', 'email'])) {
            return ['Menu saya', 'Pengaturan', 'Cara login'];
        }

        return $this->defaultSuggestions($user);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function actionsForMessage(string $message, ?User $user, ?SiteSetting $settings): array
    {
        $normalized = $this->normalize($message);

        if ($this->matches($normalized, ['kontak', 'admin', 'whatsapp', 'email'])) {
            return $this->contactActions($settings, $user);
        }

        if ($this->matches($normalized, ['laporan', 'verifikasi', 'revisi'])) {
            $actions = [$this->action('Buka Laporan', route('reports.index'))];

            if ($user?->hasPermission('reports.create')) {
                $actions[] = $this->action('Buat Laporan', route('reports.create'));
            }

            return $actions;
        }

        if ($this->matches($normalized, ['income', 'pemasukan']) && $user?->hasPermission('income.view')) {
            return [$this->action('Buka Income', route('income.index'))];
        }

        if ($this->matches($normalized, ['menu', 'fitur', 'dashboard'])) {
            return array_slice($this->menuItemsFor($user), 0, 4);
        }

        return [];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function contactActions(?SiteSetting $settings, ?User $user): array
    {
        $actions = [];

        if ($settings?->contact_email) {
            $actions[] = $this->action('Kirim Email', 'mailto:'.$settings->contact_email);
        }

        if ($settings?->contact_whatsapp && $whatsAppUrl = $this->whatsappUrl($settings->contact_whatsapp)) {
            $actions[] = $this->action('Buka WhatsApp', $whatsAppUrl);
        }

        if ($user?->hasPermission('settings.manage')) {
            $actions[] = $this->action('Buka Pengaturan', route('admin.settings.edit'));
        }

        return array_slice($actions, 0, 3);
    }

    /**
     * @return array<int, string>
     */
    private function defaultSuggestions(?User $user): array
    {
        if (! $user) {
            return ['Cara login', 'Lupa password', 'Kontak admin'];
        }

        return ['Menu saya', 'Status laporan saya', 'Cara buat laporan', 'Kontak admin'];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function menuItemsFor(?User $user): array
    {
        if (! $user) {
            return [
                $this->action('Login', route('login')),
                $this->action('Daftar', route('register')),
                $this->action('Lupa Password', route('password.request')),
            ];
        }

        $items = [];

        if ($user->hasPermission('dashboard.view')) {
            $items[] = $this->action('Dashboard', route('dashboard'));
        }

        if ($user->hasPermission('reports.view')) {
            $items[] = $this->action('Laporan Infrastruktur', route('reports.index'));
        }

        if ($user->hasPermission('income.view')) {
            $items[] = $this->action('Income', route('income.index'));
        }

        if ($user->hasPermission('users.manage')) {
            $items[] = $this->action('Kelola Pengguna', route('admin.users.index'));
        }

        if ($user->hasPermission('classrooms.manage')) {
            $items[] = $this->action('Kelola Kelas', route('admin.classrooms.index'));
        }

        if ($user->hasPermission('permissions.manage')) {
            $items[] = $this->action('Hak Akses', route('admin.permissions.index'));
        }

        if ($user->hasPermission('settings.manage')) {
            $items[] = $this->action('Pengaturan', route('admin.settings.edit'));
        }

        return $items;
    }

    /**
     * @return array{label: string, url: string}
     */
    private function action(string $label, string $url): array
    {
        return ['label' => $label, 'url' => $url];
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->toString();
    }

    private function firstName(string $value): string
    {
        return Str::of($value)->trim()->explode(' ')->filter()->first() ?? '';
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function matches(string $haystack, array $needles): bool
    {
        return Str::contains($haystack, $needles);
    }

    /**
     * @param  array<int, string>  $values
     */
    private function naturalList(array $values): string
    {
        $values = array_values(array_filter($values, fn (?string $value): bool => filled($value)));

        if ($values === []) {
            return '';
        }

        if (count($values) === 1) {
            return $values[0];
        }

        $last = array_pop($values);

        return implode(', ', $values).' dan '.$last;
    }

    private function whatsappUrl(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (! $digits) {
            return null;
        }

        if (Str::startsWith($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }

    /**
     * @param  array<int, string>  $suggestions
     * @param  array<int, array{label: string, url: string}>  $actions
     * @return array{answer: string, suggestions: array<int, string>, actions: array<int, array{label: string, url: string}>, response_id: ?string}
     */
    private function response(string $answer, array $suggestions = [], array $actions = [], ?string $responseId = null): array
    {
        return [
            'answer' => $answer,
            'suggestions' => array_values(array_unique(array_slice($suggestions, 0, 4))),
            'actions' => array_values(array_slice($actions, 0, 4)),
            'response_id' => $responseId,
        ];
    }
}
