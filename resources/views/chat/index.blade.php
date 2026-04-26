@extends('layouts.app', ['title' => 'Asisten AI'])

@php
    $quickPrompts = [
        'Menu saya',
        'Status laporan saya',
        'Cara buat laporan',
        'Kontak admin',
    ];
@endphp

@section('content')
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_22rem]">
        <div class="panel flex min-h-[40rem] flex-col overflow-hidden">
            <div class="border-b border-slate-200/70 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-400/15 text-emerald-300 shadow-lg shadow-emerald-500/10 ring-1 ring-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-7 w-7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-2.062-1.198a4.5 4.5 0 0 1-1.58-6.312L6 9.75m3.813 6.154L12 21l2.188-5.096m-4.375 0a4.5 4.5 0 0 1 4.374 0M12 21a4.5 4.5 0 0 0 4.188-5.096m-8.376 0A4.5 4.5 0 0 1 12 14.25m4.188 1.654L18 18.75l2.063-1.198a4.5 4.5 0 0 0 1.58-6.312L18 9.75m-1.813 6.154L12 14.25m6-4.5a6 6 0 1 0-12 0 6 6 0 0 0 12 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-emerald-300">Asisten Digital</p>
                            <h2 class="mt-2 text-2xl font-semibold">Asisten PH</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-white/70">
                                Tanyakan menu, laporan, status data, login, atau kontak admin. Jawaban akan menyesuaikan akses akun Anda.
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="clearChat()" class="btn-secondary border-white/10 bg-white/10 text-white hover:border-white/20 hover:bg-white/15 hover:text-white">
                        Bersihkan Percakapan
                    </button>
                </div>
            </div>

            <div id="chat-container" class="chatbot-scrollbar soft-grid flex-1 space-y-6 overflow-y-auto bg-[linear-gradient(180deg,#f8fafc_0%,#fffdf8_100%)] p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700">
                        <span class="text-[10px] font-bold tracking-[0.2em]">AI</span>
                    </div>
                    <div class="max-w-[90%] space-y-2">
                        <div class="rounded-[24px] rounded-tl-none border border-white/90 bg-white px-5 py-4 text-sm leading-7 text-slate-700 shadow-sm shadow-slate-200/70">
                            Halo {{ auth()->user()->name }}. Saya siap bantu menjelaskan menu aplikasi, laporan, verifikasi, income, atau kontak admin sekolah.
                        </div>
                        <p class="px-1 text-[11px] font-medium uppercase tracking-[0.24em] text-slate-400">Asisten PH</p>
                    </div>
                </div>
            </div>

            <div id="typing-indicator" class="hidden border-t border-slate-200/70 bg-white/80 px-6 py-3">
                <div class="flex items-center gap-3 text-sm text-slate-500">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Asisten sedang menyiapkan jawaban...
                </div>
            </div>

            <div class="border-t border-slate-200/70 bg-white p-4">
                <form id="chat-form" class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($quickPrompts as $prompt)
                            <button type="button" class="prompt-chip btn-secondary px-3 py-2 text-xs" data-prompt="{{ $prompt }}">
                                {{ $prompt }}
                            </button>
                        @endforeach
                    </div>

                    <div class="relative">
                        <label for="message-input" class="sr-only">Tulis pesan</label>
                        <textarea id="message-input" class="field min-h-[5.5rem] resize-none pr-14" placeholder="Tulis pertanyaan Anda..." maxlength="2000"></textarea>
                        <button type="submit" id="send-button" class="absolute bottom-3 right-3 inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-white transition-colors hover:bg-slate-800 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="space-y-5">
            <section class="panel px-5 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">Yang Bisa Ditanyakan</p>
                <div class="mt-4 grid gap-3">
                    <div class="rounded-[24px] border border-slate-200/70 bg-slate-50/80 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-950">Menu dan navigasi</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Minta daftar menu yang tersedia sesuai role akun Anda.</p>
                    </div>
                    <div class="rounded-[24px] border border-slate-200/70 bg-slate-50/80 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-950">Laporan dan verifikasi</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Tanyakan cara membuat laporan, status laporan, atau proses revisi.</p>
                    </div>
                    <div class="rounded-[24px] border border-slate-200/70 bg-slate-50/80 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-950">Kontak bantuan</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Minta email, WhatsApp, atau jalur bantuan lain yang tersedia.</p>
                    </div>
                </div>
            </section>

            <section class="panel px-5 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">Contoh Prompt</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($quickPrompts as $prompt)
                        <button type="button" class="prompt-chip btn-secondary px-3 py-2 text-xs" data-prompt="{{ $prompt }}">
                            {{ $prompt }}
                        </button>
                    @endforeach
                </div>
            </section>
        </aside>
    </section>

    <script>
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const chatContainer = document.getElementById('chat-container');
        const typingIndicator = document.getElementById('typing-indicator');
        const sendButton = document.getElementById('send-button');
        const promptButtons = document.querySelectorAll('.prompt-chip');

        let previousResponseId = null;

        const scrollToBottom = () => {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        };

        const formatTime = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const createActionLinks = (actions = []) => {
            if (!Array.isArray(actions) || actions.length === 0) {
                return '';
            }

            return `
                <div class="mt-3 flex flex-wrap gap-2">
                    ${actions
                        .map((action) => `
                            <a href="${action.url}" class="btn-secondary px-3 py-2 text-xs">
                                ${action.label}
                            </a>
                        `)
                        .join('')}
                </div>
            `;
        };

        const addMessage = (role, text, options = {}) => {
            const isAI = role === 'ai';
            const wrapper = document.createElement('div');
            wrapper.className = `flex items-start gap-4 ${isAI ? '' : 'justify-end'}`;

            const badge = document.createElement('div');
            badge.className = `flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl text-[10px] font-bold tracking-[0.2em] ${
                isAI
                    ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'bg-slate-950 text-white'
            }`;
            badge.textContent = isAI ? 'AI' : 'YOU';

            const content = document.createElement('div');
            content.className = `max-w-[90%] space-y-2 ${isAI ? '' : 'items-end text-right'}`;

            const bubble = document.createElement('div');
            bubble.className = `rounded-[24px] px-5 py-4 text-sm leading-7 shadow-sm ${
                isAI
                    ? 'rounded-tl-none border border-white/90 bg-white text-slate-700 shadow-slate-200/70'
                    : 'rounded-tr-none bg-slate-950 text-white shadow-slate-900/20'
            }`;

            if (options.type === 'error') {
                bubble.className = 'rounded-[24px] rounded-tl-none border border-rose-200 bg-rose-50 px-5 py-4 text-sm leading-7 text-rose-700 shadow-sm';
            }

            bubble.innerHTML = `
                <p class="whitespace-pre-wrap">${text}</p>
                ${isAI ? createActionLinks(options.actions) : ''}
            `;

            const meta = document.createElement('p');
            meta.className = 'px-1 text-[11px] font-medium uppercase tracking-[0.24em] text-slate-400';
            meta.textContent = `${isAI ? 'Asisten PH' : 'Anda'} • ${formatTime()}`;

            content.appendChild(bubble);
            content.appendChild(meta);

            if (isAI) {
                wrapper.appendChild(badge);
                wrapper.appendChild(content);
            } else {
                wrapper.appendChild(content);
                wrapper.appendChild(badge);
            }

            chatContainer.appendChild(wrapper);
            scrollToBottom();
        };

        const sendMessage = async (message) => {
            const text = String(message || '').trim();

            if (!text) {
                return;
            }

            addMessage('user', text);
            messageInput.value = '';
            typingIndicator.classList.remove('hidden');
            sendButton.disabled = true;
            scrollToBottom();

            try {
                const response = await fetch('{{ route('chat.message') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        message: text,
                        current_route: 'chat.index',
                        previous_response_id: previousResponseId,
                    }),
                });

                const data = await response.json();
                typingIndicator.classList.add('hidden');

                previousResponseId = data.response_id || previousResponseId;

                if (data.answer || data.reply) {
                    addMessage('ai', data.answer || data.reply, {
                        actions: data.actions || [],
                    });
                } else {
                    addMessage('ai', 'Respons asisten tidak valid.', { type: 'error' });
                }
            } catch (error) {
                typingIndicator.classList.add('hidden');
                addMessage('ai', 'Terjadi kesalahan saat menghubungi asisten.', { type: 'error' });
            } finally {
                sendButton.disabled = false;
                messageInput.focus();
            }
        };

        chatForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            await sendMessage(messageInput.value);
        });

        promptButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                await sendMessage(button.dataset.prompt || '');
            });
        });

        messageInput.addEventListener('keydown', async (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                await sendMessage(messageInput.value);
            }
        });

        window.clearChat = () => {
            if (!confirm('Bersihkan percakapan ini?')) {
                return;
            }

            previousResponseId = null;
            chatContainer.innerHTML = `
                <div class="flex items-start gap-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700">
                        <span class="text-[10px] font-bold tracking-[0.2em]">AI</span>
                    </div>
                    <div class="max-w-[90%] space-y-2">
                        <div class="rounded-[24px] rounded-tl-none border border-white/90 bg-white px-5 py-4 text-sm leading-7 text-slate-700 shadow-sm shadow-slate-200/70">
                            Halo {{ auth()->user()->name }}. Saya siap bantu menjelaskan menu aplikasi, laporan, verifikasi, income, atau kontak admin sekolah.
                        </div>
                        <p class="px-1 text-[11px] font-medium uppercase tracking-[0.24em] text-slate-400">Asisten PH</p>
                    </div>
                </div>
            `;
        };
    </script>
@endsection
