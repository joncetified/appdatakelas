import './bootstrap';
import { initializeSupportChatbot } from './chatbot';

const numberFormatter = new Intl.NumberFormat('id-ID');
const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const escapeHtml = (value) =>
    String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

const lockInputTranslation = (root = document) => {
    root.querySelectorAll('input, textarea, select, option, [data-user-content]').forEach((element) => {
        element.setAttribute('translate', 'no');
        element.classList.add('notranslate');

        if ('spellcheck' in element) {
            element.spellcheck = false;
        }
    });
};

const initializeInputGuards = () => {
    lockInputTranslation();

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches('input, textarea, select, option, [data-user-content]')) {
                    node.setAttribute('translate', 'no');
                    node.classList.add('notranslate');
                    return;
                }

                lockInputTranslation(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
};

const formatChartValue = (value, format) => {
    if (format === 'currency') {
        return currencyFormatter.format(Number(value || 0));
    }

    return numberFormatter.format(Number(value || 0));
};

const initializeReportRepeater = () => {
    const repeater = document.querySelector('[data-item-repeater]');

    if (!repeater) {
        return;
    }

    const list = repeater.querySelector('[data-item-list]');
    const template = repeater.querySelector('template[data-item-template]');
    const addButton = repeater.querySelector('[data-add-item]');

    if (!list || !template || !addButton) {
        return;
    }

    const reindexRows = () => {
        [...list.querySelectorAll('[data-item-row]')].forEach((row, index) => {
            row.querySelectorAll('[data-field-name]').forEach((input) => {
                const field = input.getAttribute('data-field-name');
                input.name = `items[${index}][${field}]`;
            });

            row.querySelectorAll('[data-field-id]').forEach((input) => {
                const field = input.getAttribute('data-field-id');
                input.id = `items_${index}_${field}`;
            });

            row.querySelectorAll('[data-field-label]').forEach((label) => {
                const field = label.getAttribute('data-field-label');
                label.setAttribute('for', `items_${index}_${field}`);
            });
        });
    };

    addButton.addEventListener('click', () => {
        const index = list.querySelectorAll('[data-item-row]').length;
        const markup = template.innerHTML.replaceAll('__INDEX__', String(index));

        list.insertAdjacentHTML('beforeend', markup);
        reindexRows();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-item]');

        if (!button) {
            return;
        }

        const row = button.closest('[data-item-row]');

        if (!row) {
            return;
        }

        if (list.querySelectorAll('[data-item-row]').length === 1) {
            row.querySelectorAll('input, textarea').forEach((field) => {
                if (field.type === 'number') {
                    field.value = field.name.includes('damaged_units') ? '0' : '';
                    return;
                }

                field.value = '';
            });

            return;
        }

        row.remove();
        reindexRows();
    });

    reindexRows();
};

const renderBarChart = (container, chart) => {
    const dataset = chart.datasets?.[0];

    if (!dataset || !Array.isArray(chart.labels) || chart.labels.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-500">Belum ada data untuk ditampilkan.</p>';
        return;
    }

    const values = dataset.data.map((value) => Number(value || 0));
    const maxValue = Math.max(...values, 1);
    const width = 720;
    const height = 320;
    const chartHeight = 210;
    const baseLine = 250;
    const step = width / Math.max(values.length, 1);
    const barWidth = Math.min(56, step * 0.58);

    const bars = values
        .map((value, index) => {
            const barHeight = maxValue === 0 ? 0 : (value / maxValue) * chartHeight;
            const x = index * step + (step - barWidth) / 2;
            const y = baseLine - barHeight;
            const label = escapeHtml(chart.labels[index] ?? '');
            const valueLabel = escapeHtml(formatChartValue(value, chart.format));
            const color = escapeHtml(dataset.backgroundColor || '#0f172a');

            return `
                <g>
                    <rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="16" fill="${color}" opacity="0.92"></rect>
                    <text x="${x + barWidth / 2}" y="${Math.max(18, y - 8)}" text-anchor="middle" font-size="11" fill="#475569">${valueLabel}</text>
                    <text x="${x + barWidth / 2}" y="${baseLine + 26}" text-anchor="middle" font-size="11" fill="#64748b">${label}</text>
                </g>
            `;
        })
        .join('');

    container.innerHTML = `
        <svg viewBox="0 0 ${width} ${height}" class="h-[320px] w-full" role="img" aria-label="${escapeHtml(chart.title || 'Chart')}">
            <line x1="24" y1="${baseLine}" x2="${width - 24}" y2="${baseLine}" stroke="#cbd5e1" stroke-width="2"></line>
            ${bars}
        </svg>
    `;
};

const polarToCartesian = (centerX, centerY, radius, angleInDegrees) => {
    const angleInRadians = ((angleInDegrees - 90) * Math.PI) / 180.0;

    return {
        x: centerX + radius * Math.cos(angleInRadians),
        y: centerY + radius * Math.sin(angleInRadians),
    };
};

const describeArc = (centerX, centerY, radius, startAngle, endAngle) => {
    const start = polarToCartesian(centerX, centerY, radius, endAngle);
    const end = polarToCartesian(centerX, centerY, radius, startAngle);
    const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';

    return ['M', centerX, centerY, 'L', start.x, start.y, 'A', radius, radius, 0, largeArcFlag, 0, end.x, end.y, 'Z'].join(' ');
};

const renderPieChart = (container, chart) => {
    const dataset = chart.datasets?.[0];

    if (!dataset || !Array.isArray(chart.labels) || chart.labels.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-500">Belum ada data untuk ditampilkan.</p>';
        return;
    }

    const values = dataset.data.map((value) => Number(value || 0));
    const total = values.reduce((sum, value) => sum + value, 0);

    if (total <= 0) {
        container.innerHTML = '<p class="text-sm text-slate-500">Belum ada data untuk ditampilkan.</p>';
        return;
    }

    let startAngle = 0;
    const radius = 108;
    const segments = values
        .map((value, index) => {
            const angle = (value / total) * 360;
            const endAngle = startAngle + angle;
            const color = escapeHtml(dataset.backgroundColor?.[index] || '#cbd5e1');
            const path = describeArc(150, 150, radius, startAngle, endAngle);

            startAngle = endAngle;

            return `<path d="${path}" fill="${color}"></path>`;
        })
        .join('');

    const legend = chart.labels
        .map((label, index) => {
            const color = escapeHtml(dataset.backgroundColor?.[index] || '#cbd5e1');
            const value = escapeHtml(formatChartValue(values[index], chart.format));

            return `
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full" style="background:${color}"></span>
                        <span class="text-sm text-slate-700">${escapeHtml(label)}</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-950">${value}</span>
                </div>
            `;
        })
        .join('');

    container.innerHTML = `
        <div class="grid gap-6 lg:grid-cols-[0.95fr,1.05fr] lg:items-center">
            <svg viewBox="0 0 300 300" class="mx-auto h-[280px] w-[280px]" role="img" aria-label="${escapeHtml(chart.title || 'Pie chart')}">
                ${segments}
                <circle cx="150" cy="150" r="46" fill="#f8fafc"></circle>
                <text x="150" y="148" text-anchor="middle" font-size="14" fill="#64748b">Total</text>
                <text x="150" y="170" text-anchor="middle" font-size="16" font-weight="700" fill="#0f172a">${escapeHtml(formatChartValue(total, chart.format))}</text>
            </svg>
            <div class="space-y-3">${legend}</div>
        </div>
    `;
};

const initializeCharts = () => {
    document.querySelectorAll('[data-chart]').forEach((element) => {
        const raw = element.getAttribute('data-chart');

        if (!raw) {
            return;
        }

        try {
            const chart = JSON.parse(raw);

            if (chart.type === 'pie') {
                renderPieChart(element, chart);
                return;
            }

            renderBarChart(element, chart);
        } catch (error) {
            element.innerHTML = '<p class="text-sm text-rose-600">Chart gagal dimuat.</p>';
        }
    });
};

const initializeUiScaleToggle = () => {
    const button = document.querySelector('[data-ui-scale-toggle]');

    if (!button) {
        return;
    }

    const storageKey = 'appdatakelas.uiExpanded';
    const expandIcon = button.querySelector('[data-ui-scale-expand]');
    const shrinkIcon = button.querySelector('[data-ui-scale-shrink]');
    const fullscreenTarget = document.documentElement;

    const fullscreenElement = () =>
        document.fullscreenElement
        || document.webkitFullscreenElement
        || document.msFullscreenElement
        || null;

    const requestFullscreen = () => {
        if (fullscreenTarget.requestFullscreen) {
            return fullscreenTarget.requestFullscreen();
        }

        if (fullscreenTarget.webkitRequestFullscreen) {
            return fullscreenTarget.webkitRequestFullscreen();
        }

        if (fullscreenTarget.msRequestFullscreen) {
            return fullscreenTarget.msRequestFullscreen();
        }

        return Promise.reject(new Error('Fullscreen tidak didukung.'));
    };

    const exitFullscreen = () => {
        if (document.exitFullscreen) {
            return document.exitFullscreen();
        }

        if (document.webkitExitFullscreen) {
            return document.webkitExitFullscreen();
        }

        if (document.msExitFullscreen) {
            return document.msExitFullscreen();
        }

        return Promise.reject(new Error('Keluar fullscreen tidak didukung.'));
    };

    const isExpanded = () => Boolean(fullscreenElement()) || document.body.classList.contains('ui-expanded');

    const syncState = () => {
        const expanded = isExpanded();

        button.setAttribute('aria-pressed', String(expanded));
        button.setAttribute('aria-label', expanded ? 'Keluar dari layar penuh' : 'Buka layar penuh');
        button.setAttribute('title', expanded ? 'Keluar layar penuh' : 'Layar penuh');
        expandIcon?.classList.toggle('hidden', expanded);
        shrinkIcon?.classList.toggle('hidden', !expanded);
    };

    document.body.classList.toggle('ui-expanded', window.localStorage.getItem(storageKey) === '1');

    const toggleFallbackExpanded = () => {
        const expanded = !document.body.classList.contains('ui-expanded');

        document.body.classList.toggle('ui-expanded', expanded);
        window.localStorage.setItem(storageKey, expanded ? '1' : '0');
        syncState();
    };

    button.addEventListener('click', async () => {
        try {
            if (fullscreenElement()) {
                await exitFullscreen();
                document.body.classList.remove('ui-expanded');
                window.localStorage.removeItem(storageKey);
            } else {
                await requestFullscreen();
                document.body.classList.add('ui-expanded');
                window.localStorage.setItem(storageKey, '1');
            }
        } catch (error) {
            toggleFallbackExpanded();
        }

        syncState();
    });

    document.addEventListener('fullscreenchange', syncState);
    document.addEventListener('webkitfullscreenchange', syncState);
    document.addEventListener('MSFullscreenChange', syncState);
    syncState();
};

const makeSquareAvatarFile = (image, cropState, size = 640) =>
    new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        canvas.width = size;
        canvas.height = size;

        if (!context) {
            reject(new Error('Canvas tidak tersedia.'));
            return;
        }

        context.fillStyle = '#f8fafc';
        context.fillRect(0, 0, size, size);

        const scale = cropState.scale;
        const displayWidth = image.naturalWidth * scale;
        const displayHeight = image.naturalHeight * scale;
        const boxSize = cropState.boxSize;
        const scaleToCanvas = size / boxSize;
        const targetWidth = displayWidth * scaleToCanvas;
        const targetHeight = displayHeight * scaleToCanvas;
        const targetX = (boxSize / 2 + cropState.x - displayWidth / 2) * scaleToCanvas;
        const targetY = (boxSize / 2 + cropState.y - displayHeight / 2) * scaleToCanvas;

        context.drawImage(image, targetX, targetY, targetWidth, targetHeight);
        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    reject(new Error('Gagal membuat crop avatar.'));
                    return;
                }

                resolve(blob);
            },
            'image/jpeg',
            0.9,
        );
    });

const loadImageFromFile = (file) =>
    new Promise((resolve, reject) => {
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);

        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error('Gagal membaca gambar.'));
        };

        image.src = objectUrl;
    });

const initializeAvatarCrop = () => {
    const input = document.querySelector('[data-avatar-input]');

    if (!input) {
        return;
    }

    const form = input.closest('form');
    const previewWrap = document.querySelector('[data-avatar-preview-wrap]');
    const preview = document.querySelector('[data-avatar-preview]');
    const cropBox = document.querySelector('[data-avatar-crop-box]');
    const cropImage = document.querySelector('[data-avatar-crop-image]');
    const zoomInput = document.querySelector('[data-avatar-zoom]');
    let croppedFile = null;
    let originalFile = null;
    let loadedImage = null;
    let isDragging = false;
    let dragStart = { x: 0, y: 0 };
    let startOffset = { x: 0, y: 0 };
    const cropState = {
        boxSize: 176,
        baseScale: 1,
        scale: 1,
        zoom: 1,
        x: 0,
        y: 0,
    };

    const applyCroppedFile = (file) => {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
    };

    const clampCrop = () => {
        if (!loadedImage) {
            return;
        }

        const displayWidth = loadedImage.naturalWidth * cropState.scale;
        const displayHeight = loadedImage.naturalHeight * cropState.scale;
        const maxX = Math.max(0, (displayWidth - cropState.boxSize) / 2);
        const maxY = Math.max(0, (displayHeight - cropState.boxSize) / 2);

        cropState.x = Math.max(-maxX, Math.min(maxX, cropState.x));
        cropState.y = Math.max(-maxY, Math.min(maxY, cropState.y));
    };

    const updateCropFile = async () => {
        if (!loadedImage || !originalFile) {
            return;
        }

        const blob = await makeSquareAvatarFile(loadedImage, cropState);
        croppedFile = new File([blob], originalFile.name.replace(/\.[^.]+$/, '.jpg'), {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });

        applyCroppedFile(croppedFile);

        if (preview) {
            if (preview.dataset.objectUrl) {
                URL.revokeObjectURL(preview.dataset.objectUrl);
            }

            const url = URL.createObjectURL(croppedFile);
            preview.dataset.objectUrl = url;
            preview.src = url;
        }
    };

    const syncCropUi = async () => {
        if (!loadedImage || !cropImage) {
            return;
        }

        clampCrop();
        cropImage.style.width = `${loadedImage.naturalWidth * cropState.scale}px`;
        cropImage.style.height = `${loadedImage.naturalHeight * cropState.scale}px`;
        cropImage.style.transform = `translate(calc(-50% + ${cropState.x}px), calc(-50% + ${cropState.y}px))`;
        await updateCropFile();
    };

    input.addEventListener('change', async () => {
        const file = input.files?.[0];

        croppedFile = null;
        originalFile = null;
        loadedImage = null;

        if (!file || !file.type.startsWith('image/')) {
            previewWrap?.classList.add('hidden');
            previewWrap?.classList.remove('flex');
            return;
        }

        try {
            originalFile = file;
            loadedImage = await loadImageFromFile(file);

            if (cropImage && cropBox) {
                cropState.boxSize = cropBox.clientWidth || 176;
                cropState.baseScale = Math.max(cropState.boxSize / loadedImage.naturalWidth, cropState.boxSize / loadedImage.naturalHeight);
                cropState.zoom = 1;
                cropState.scale = cropState.baseScale;
                cropState.x = 0;
                cropState.y = 0;
                cropImage.src = URL.createObjectURL(file);
                if (zoomInput) {
                    zoomInput.value = '1';
                }
            }

            if (previewWrap) {
                previewWrap.classList.remove('hidden');
                previewWrap.classList.add('flex');
            }

            await syncCropUi();
        } catch (error) {
            console.error('Gagal crop avatar.', error);
        }
    });

    zoomInput?.addEventListener('input', async () => {
        cropState.zoom = Number(zoomInput.value || 1);
        cropState.scale = cropState.baseScale * cropState.zoom;
        await syncCropUi();
    });

    cropBox?.addEventListener('pointerdown', (event) => {
        if (!loadedImage) {
            return;
        }

        isDragging = true;
        dragStart = { x: event.clientX, y: event.clientY };
        startOffset = { x: cropState.x, y: cropState.y };
        cropBox.setPointerCapture(event.pointerId);
    });

    cropBox?.addEventListener('pointermove', async (event) => {
        if (!isDragging) {
            return;
        }

        cropState.x = startOffset.x + event.clientX - dragStart.x;
        cropState.y = startOffset.y + event.clientY - dragStart.y;
        await syncCropUi();
    });

    const stopDragging = () => {
        isDragging = false;
    };

    cropBox?.addEventListener('pointerup', stopDragging);
    cropBox?.addEventListener('pointercancel', stopDragging);

    form?.addEventListener('submit', () => {
        if (croppedFile) {
            applyCroppedFile(croppedFile);
        }
    });
};

const initializeSidebarDrawer = () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeButton = document.querySelector('[data-sidebar-close]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    if (!sidebar || !openButton || !backdrop) {
        return;
    }

    const setOpen = (open) => {
        sidebar.classList.toggle('-translate-x-[115%]', !open);
        sidebar.classList.toggle('translate-x-0', open);
        backdrop.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        openButton.setAttribute('aria-expanded', String(open));
    };

    openButton.addEventListener('click', () => setOpen(true));
    closeButton?.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
};

const safelyInitialize = (initializer) => {
    try {
        initializer();
    } catch (error) {
        console.error('Gagal memuat komponen halaman.', error);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    [
        initializeInputGuards,
        initializeSidebarDrawer,
        initializeReportRepeater,
        initializeCharts,
        initializeUiScaleToggle,
        initializeAvatarCrop,
        initializeSupportChatbot,
    ].forEach(safelyInitialize);
});
