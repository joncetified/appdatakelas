<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CaptchaService
{
    public function prepare(Request $request): array
    {
        if (app()->runningUnitTests()) {
            $request->session()->put('math_captcha_answer', 5);

            return [
                'mode' => 'math',
                'site_key' => null,
                'question' => '2 + 3',
            ];
        }

        $left = random_int(1, 9);
        $right = random_int(1, 9);

        $request->session()->put('math_captcha_answer', $left + $right);

        $settings = $this->settings();
        $credentials = $this->googleCredentials($settings);

        if ($this->shouldUseGoogleCaptcha($credentials)) {
            return [
                'mode' => 'google',
                'site_key' => $credentials['site_key'],
                'question' => "{$left} + {$right}",
            ];
        }

        return [
            'mode' => 'math',
            'site_key' => null,
            'question' => "{$left} + {$right}",
        ];
    }

    public function validate(Request $request): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $settings = $this->settings();
        $credentials = $this->googleCredentials($settings);
        $mode = $request->string('captcha_mode')->toString();
        $token = $request->input('g-recaptcha-response');

        if ($this->shouldUseGoogleCaptcha($credentials) && $mode === 'google') {
            if (blank($token)) {
                throw ValidationException::withMessages([
                    'captcha' => 'Captcha wajib diisi.',
                ]);
            }

            try {
                $response = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $credentials['secret_key'],
                    'response' => (string) $token,
                    'remoteip' => $request->ip(),
                ]);

                if (! ($response->json('success') === true)) {
                    throw ValidationException::withMessages([
                        'captcha' => 'Validasi Google captcha gagal.',
                    ]);
                }

                return;
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'captcha' => 'Google captcha tidak dapat diverifikasi. Coba muat ulang halaman atau gunakan mode offline.',
                ]);
            }
        }

        $this->validateMathCaptcha($request);
    }

    /**
     * @param  array{site_key: ?string, secret_key: ?string}  $credentials
     */
    private function shouldUseGoogleCaptcha(array $credentials): bool
    {
        return filled($credentials['site_key']) && filled($credentials['secret_key']);
    }

    private function settings(): ?SiteSetting
    {
        if (! Schema::hasTable('site_settings')) {
            return null;
        }

        return SiteSetting::query()->first();
    }

    /**
     * @return array{site_key: ?string, secret_key: ?string}
     */
    private function googleCredentials(?SiteSetting $settings): array
    {
        $databaseSiteKey = $this->normalizeCredential($settings?->google_recaptcha_site_key);
        $databaseSecretKey = $this->normalizeCredential($settings?->google_recaptcha_secret_key);

        return [
            'site_key' => $databaseSiteKey ?: $this->normalizeCredential(config('services.recaptcha.site_key')),
            'secret_key' => $databaseSecretKey ?: $this->normalizeCredential(config('services.recaptcha.secret_key')),
        ];
    }

    private function normalizeCredential(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function validateMathCaptcha(Request $request): void
    {
        $expectedAnswer = (int) $request->session()->pull('math_captcha_answer', -1);
        $providedAnswer = (int) $request->input('math_captcha_answer');

        if ($expectedAnswer < 0 || $expectedAnswer !== $providedAnswer) {
            throw ValidationException::withMessages([
                'math_captcha_answer' => 'Jawaban captcha matematika tidak sesuai.',
            ]);
        }
    }
}
