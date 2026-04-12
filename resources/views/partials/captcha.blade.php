<div
    data-captcha-root
    data-google-enabled="{{ filled($captcha['site_key'] ?? null) ? '1' : '0' }}"
    class="space-y-4"
>
    <input
        type="hidden"
        name="captcha_mode"
        value="{{ filled($captcha['site_key'] ?? null) && (($captcha['mode'] ?? null) === 'google') ? 'google' : 'math' }}"
        data-captcha-mode
    >
    <div data-google-section @class(['hidden' => ! filled($captcha['site_key'] ?? null)])>
        <label class="label">Google reCAPTCHA</label>
        <p class="mt-2 text-sm text-slate-600">
            Saat online, form akan memakai Google reCAPTCHA. Jika offline atau script Google gagal dimuat, sistem otomatis pindah ke captcha matematika.
        </p>
        @if (filled($captcha['site_key'] ?? null))
            <div class="mt-3 g-recaptcha" data-sitekey="{{ $captcha['site_key'] }}"></div>
        @endif
        @error('captcha')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div data-math-section @class(['hidden' => filled($captcha['site_key'] ?? null) && (($captcha['mode'] ?? null) === 'google')])>
        <label for="math_captcha_answer" class="label">Captcha Matematika</label>
        <p class="mt-2 text-sm text-slate-600">
            Jawab soal ini untuk melanjutkan:
            <span class="font-semibold text-slate-950">{{ $captcha['question'] ?? '1 + 1' }}</span>
        </p>
        <input
            id="math_captcha_answer"
            name="math_captcha_answer"
            type="number"
            class="field mt-2"
            data-math-input
            {{ filled($captcha['site_key'] ?? null) && (($captcha['mode'] ?? null) === 'google') ? '' : 'required' }}
        >
        @error('math_captcha_answer')
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

@if (filled($captcha['site_key'] ?? null))
    <script>
        (() => {
            const script = document.currentScript;
            const root = script.previousElementSibling;

            if (!root) {
                return;
            }

            const googleSection = root.querySelector('[data-google-section]');
            const mathSection = root.querySelector('[data-math-section]');
            const mathInput = root.querySelector('[data-math-input]');
            const captchaMode = root.querySelector('[data-captcha-mode]');

            const showGoogle = () => {
                if (!googleSection || !mathSection || !mathInput || !captchaMode) {
                    return;
                }

                googleSection.classList.remove('hidden');
                mathSection.classList.add('hidden');
                mathInput.required = false;
                captchaMode.value = 'google';
            };

            const showMath = () => {
                if (!googleSection || !mathSection || !mathInput || !captchaMode) {
                    return;
                }

                googleSection.classList.add('hidden');
                mathSection.classList.remove('hidden');
                mathInput.required = true;
                captchaMode.value = 'math';
            };

            const pollGoogleCaptcha = () => {
                let attempts = 0;

                const timer = window.setInterval(() => {
                    attempts += 1;

                    if (!navigator.onLine) {
                        window.clearInterval(timer);
                        showMath();
                        return;
                    }

                    if (window.grecaptcha) {
                        window.clearInterval(timer);
                        showGoogle();
                        return;
                    }

                    if (attempts >= 10) {
                        window.clearInterval(timer);
                        showMath();
                    }
                }, 500);
            };

            window.addEventListener('online', pollGoogleCaptcha);
            window.addEventListener('offline', showMath);
            window.addEventListener('infra:recaptcha-failed', showMath);

            if (navigator.onLine) {
                pollGoogleCaptcha();
            } else {
                showMath();
            }
        })();
    </script>
    <script
        src="https://www.google.com/recaptcha/api.js"
        async
        defer
        onerror="window.dispatchEvent(new Event('infra:recaptcha-failed'))"
    ></script>
@endif
