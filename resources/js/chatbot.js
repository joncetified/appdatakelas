const escapeHtml = (value) =>
    String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

const normalizeText = (value) => escapeHtml(value).replaceAll('\n', '<br>');

const normalizeList = (value) => (Array.isArray(value) ? value.filter(Boolean) : []);

const buildShell = (brandName, botName) => `
    <div class="pointer-events-none fixed bottom-5 right-4 z-50 flex flex-col items-end gap-3 sm:bottom-6 sm:right-6">
        <section data-chatbot-panel class="pointer-events-auto hidden w-[min(92vw,24rem)] overflow-hidden rounded-[28px] border border-white/80 bg-white/95 shadow-[0_24px_80px_-35px_rgba(15,23,42,0.55)] backdrop-blur-xl">
            <div class="flex items-start justify-between gap-4 bg-slate-950 px-5 py-4 text-white">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-emerald-300">Asisten PH</p>
                    <h2 class="mt-2 text-lg font-semibold">${escapeHtml(botName)}</h2>
                    <p class="mt-1 text-xs leading-5 text-white/70">Teman bantu digital untuk ${escapeHtml(brandName)}. Tulis pertanyaan bebas tanpa menu pilihan.</p>
                </div>
                <button type="button" data-chatbot-close class="rounded-full border border-white/15 px-3 py-1 text-xs font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">
                    Tutup
                </button>
            </div>
            <div data-chatbot-messages class="chatbot-scrollbar max-h-[58vh] space-y-3 overflow-y-auto bg-[linear-gradient(180deg,#f8fafc_0%,#fffaf0_100%)] px-4 py-4"></div>
            <form data-chatbot-form class="border-t border-slate-200 bg-white px-4 py-4">
                <label for="chatbot-input" class="sr-only">Ketik pertanyaan</label>
                <div class="flex items-end gap-3">
                    <input id="chatbot-input" data-chatbot-input type="text" class="field min-w-0 flex-1" placeholder="Tanyakan apa saja...">
                    <button type="submit" data-chatbot-submit class="btn-primary shrink-0">
                        Kirim
                    </button>
                </div>
            </form>
        </section>

        <button type="button" data-chatbot-open class="pointer-events-auto inline-flex items-center gap-3 rounded-full bg-slate-950 px-5 py-4 text-sm font-semibold text-white shadow-[0_18px_48px_-24px_rgba(15,23,42,0.65)] transition hover:-translate-y-0.5 hover:bg-slate-800">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-400/20 text-base text-emerald-300">PH</span>
            <span>${escapeHtml(botName)}</span>
        </button>
    </div>
`;

const renderMessage = (message) => {
    const isAssistant = message.role === 'assistant';
    const alignClass = isAssistant ? 'pr-8' : 'pl-8';
    const labelClass = isAssistant ? 'text-emerald-600' : 'text-right text-slate-500';
    const bubbleClass = isAssistant
        ? 'border border-white/80 bg-white text-slate-700 shadow-sm shadow-slate-200/70'
        : 'bg-slate-950 text-white shadow-sm shadow-slate-950/15';

    const actions = normalizeList(message.actions)
        .map(
            (action) => `
                <a href="${escapeHtml(action.url)}" class="btn-secondary px-3 py-2 text-xs">
                    ${escapeHtml(action.label)}
                </a>
            `,
        )
        .join('');

    return `
        <article class="${alignClass}">
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.24em] ${labelClass}">
                ${isAssistant ? escapeHtml(message.assistantLabel || 'Asisten PH') : 'Anda'}
            </p>
            <div class="rounded-[22px] px-4 py-3 text-sm leading-6 ${bubbleClass}">
                <p>${normalizeText(message.text)}</p>
                ${actions ? `<div class="mt-3 flex flex-wrap gap-2">${actions}</div>` : ''}
            </div>
        </article>
    `;
};

const renderTyping = (botName) => `
    <article class="pr-8">
        <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-600">${escapeHtml(botName)}</p>
        <div class="rounded-[22px] border border-white/80 bg-white px-4 py-3 text-sm text-slate-500 shadow-sm shadow-slate-200/70">
            Sedang menyiapkan jawaban...
        </div>
    </article>
`;

const firstName = (name) => String(name || '').trim().split(/\s+/)[0] || '';

const openingMessage = (config) => {
    if (config.isGuest) {
        return `Halo, saya ${config.botName}. Kalau ada yang ingin ditanyakan soal login, laporan, fitur utama, atau kontak sekolah, saya bantu jawab.`;
    }

    const lead = firstName(config.userName);
    const greeting = lead ? `Halo ${lead}.` : 'Halo.';

    return `${greeting} Saya ${config.botName}. Kalau ada yang ingin Anda tanyakan tentang ${config.brandName}, saya bantu jawab sesuai akses ${config.roleLabel || 'Anda'}.`;
};

export const initializeSupportChatbot = () => {
    const root = document.querySelector('#support-chatbot');

    if (!root) {
        return;
    }

    const rawConfig = root.getAttribute('data-chatbot');

    if (!rawConfig) {
        return;
    }

    let config = null;

    try {
        config = JSON.parse(rawConfig);
    } catch (error) {
        return;
    }

    if (!config?.endpoint) {
        return;
    }

    root.innerHTML = buildShell(config.brandName || 'Aplikasi', config.botName || 'Asisten PH');

    const panel = root.querySelector('[data-chatbot-panel]');
    const openButton = root.querySelector('[data-chatbot-open]');
    const closeButton = root.querySelector('[data-chatbot-close]');
    const form = root.querySelector('[data-chatbot-form]');
    const input = root.querySelector('[data-chatbot-input]');
    const submitButton = root.querySelector('[data-chatbot-submit]');
    const messages = root.querySelector('[data-chatbot-messages]');

    if (!panel || !openButton || !closeButton || !form || !input || !submitButton || !messages) {
        return;
    }

    const state = {
        isLoading: false,
        items: [],
        previousResponseId: null,
    };

    const render = () => {
        const markup = state.items
            .map((item) => renderMessage({ ...item, assistantLabel: config.botName || 'Asisten PH' }))
            .join('');
        messages.innerHTML = `${markup}${state.isLoading ? renderTyping(config.botName || 'Asisten PH') : ''}`;
        messages.scrollTop = messages.scrollHeight;
    };

    const pushMessage = (role, text, options = {}) => {
        state.items.push({
            role,
            text,
            actions: normalizeList(options.actions),
            suggestions: normalizeList(options.suggestions),
        });

        if (state.items.length > 18) {
            state.items.shift();
        }

        render();
    };

    const setOpen = (value) => {
        panel.classList.toggle('hidden', !value);
        openButton.classList.toggle('hidden', value);

        if (value) {
            input.focus();
        }
    };

    const sendMessage = async (text) => {
        const message = String(text || '').trim();

        if (!message || state.isLoading) {
            return;
        }

        pushMessage('user', message);
        input.value = '';
        state.isLoading = true;
        render();

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await window.axios.post(
                config.endpoint,
                {
                    message,
                    current_route: config.currentRoute || null,
                    previous_response_id: state.previousResponseId,
                },
                {
                    headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
                },
            );

            state.previousResponseId = response.data?.response_id || state.previousResponseId;

            pushMessage('assistant', response.data?.answer || 'Saya belum punya jawaban untuk itu.', {
                actions: response.data?.actions,
            });
        } catch (error) {
            pushMessage('assistant', `${config.botName || 'Asisten PH'} lagi belum bisa menjawab sekarang. Coba lagi sebentar atau hubungi admin.`);
        } finally {
            state.isLoading = false;
            render();
            input.focus();
        }
    };

    openButton.addEventListener('click', () => setOpen(true));
    closeButton.addEventListener('click', () => setOpen(false));

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
    });

    pushMessage('assistant', openingMessage(config));
};
