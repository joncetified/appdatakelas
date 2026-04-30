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

    const isExpanded = () => document.body.classList.contains('ui-expanded');

    const syncState = () => {
        const expanded = isExpanded();

        button.setAttribute('aria-pressed', String(expanded));
        button.setAttribute('aria-label', expanded ? 'Kecilkan tampilan web app' : 'Perbesar tampilan web app');
        button.setAttribute('title', expanded ? 'Kecilkan UI' : 'Perbesar UI');
        expandIcon?.classList.toggle('hidden', expanded);
        shrinkIcon?.classList.toggle('hidden', !expanded);
    };

    document.body.classList.toggle('ui-expanded', window.localStorage.getItem(storageKey) === '1');

    button.addEventListener('click', () => {
        const expanded = !isExpanded();

        document.body.classList.toggle('ui-expanded', expanded);
        window.localStorage.setItem(storageKey, expanded ? '1' : '0');
        syncState();
    });

    syncState();
};

document.addEventListener('DOMContentLoaded', () => {
    initializeReportRepeater();
    initializeCharts();
    initializeUiScaleToggle();
    initializeSupportChatbot();
});
