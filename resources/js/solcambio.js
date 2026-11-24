/**
 * Interfaz Solcambio.
 *
 * Este archivo NO valida montos: solo envía lo que escribe el usuario a la API
 * y muestra lo que ella responde. La API es la única que decide qué es válido.
 */

const HISTORY_KEY = 'solcambio-history';
const THEME_KEY = 'solcambio-theme';
const HISTORY_LIMIT = 5;
const TOAST_DURATION_MS = 3000;

const DIRECTIONS = {
    clpToUsd: {
        label: 'De CLP a USD',
        endpoint: '/api/convert',
        prefix: '$',
        placeholder: '19000',
        hint: 'Solo pesos enteros, sin puntos ni comas.',
        quickAmounts: [950, 1000, 19000, 100000],
    },
    usdToClp: {
        label: 'De USD a CLP',
        endpoint: '/api/convert/usd-to-clp',
        prefix: 'US$',
        placeholder: '20.50',
        hint: 'Usa punto para decimales, por ejemplo 20.50.',
        quickAmounts: [1, 10, 20, 100],
    },
};

const clpFormatter = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 });
const usdFormatter = new Intl.NumberFormat('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const formatClp = (value) => `$${clpFormatter.format(value)}`;
const formatUsd = (value) => `US$ ${usdFormatter.format(value)}`;

const state = {
    direction: 'clpToUsd',
    toastTimer: null,
};

const el = {};

// ---------------------------------------------------------------------------
// Tasa de cambio
// ---------------------------------------------------------------------------

async function loadRate() {
    try {
        const response = await fetch('/api/exchange-rate', { headers: { Accept: 'application/json' } });
        const data = await response.json();
        el.rateBanner.textContent = `Cambio de hoy 1 ${data.to} = ${data.exchange_rate} ${data.from}`;
    } catch (error) {
        el.rateBanner.textContent = 'Cambio de hoy no disponible';
    }
}

// ---------------------------------------------------------------------------
// Dirección de la conversión
// ---------------------------------------------------------------------------

function toggleDirection() {
    state.direction = state.direction === 'clpToUsd' ? 'usdToClp' : 'clpToUsd';
    renderDirection();
    el.amount.value = '';
    setButton('idle');
    el.amount.focus();
}

function renderDirection() {
    const direction = DIRECTIONS[state.direction];
    const formatChip = (value) => `${direction.prefix}${state.direction === 'clpToUsd' ? '' : ' '}${clpFormatter.format(value)}`;

    el.directionLabel.textContent = direction.label;
    el.amountPrefix.textContent = direction.prefix;
    el.amount.placeholder = direction.placeholder;
    el.amountHint.textContent = direction.hint;

    el.quickAmounts.replaceChildren(
        ...direction.quickAmounts.map((value) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className =
                'rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-600 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 dark:border-violet-900 dark:text-violet-200 dark:hover:border-violet-600 dark:hover:bg-violet-950';
            chip.textContent = formatChip(value);
            chip.addEventListener('click', () => {
                el.amount.value = String(value);
                setButton('idle');
                el.amount.focus();
            });
            return chip;
        }),
    );
}

// ---------------------------------------------------------------------------
// Conversión
// ---------------------------------------------------------------------------

/**
 * Arma el cuerpo de la petición. No valida: solo traduce el texto a JSON.
 * Vacío → {}, texto con forma de número → número, cualquier otra cosa → texto.
 */
function buildPayload(rawValue) {
    const text = rawValue.trim();

    if (text === '') {
        return {};
    }

    const asNumber = Number(text);

    return { amount: Number.isFinite(asNumber) ? asNumber : text };
}

async function convert(event) {
    event.preventDefault();

    const directionKey = state.direction;
    const direction = DIRECTIONS[directionKey];
    setButton('loading');

    try {
        const response = await fetch(direction.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(buildPayload(el.amount.value)),
        });
        const data = await response.json();

        renderJson(response.status, data);
        renderResult(data, directionKey);

        if (data.success) {
            saveToHistory(data, directionKey);
            setButton('success');
        } else {
            setButton('idle');
            showToast(data.message);
        }
    } catch (error) {
        renderJson('—', { error: 'No se pudo conectar con el servidor.' });
        setButton('idle');
        showToast('No se pudo conectar con el servidor.');
    }
}

const BUTTON_TEXT = {
    idle: 'Convertir',
    loading: 'Convirtiendo…',
    success: 'CONVERSIÓN REALIZADA CON ÉXITO',
};

/**
 * Estados del botón: idle (listo), loading (esperando a la API) y success.
 */
function setButton(status) {
    el.convertButton.disabled = status === 'loading';
    el.convertButton.textContent = BUTTON_TEXT[status];
}

/**
 * Texto "origen → destino" de una respuesta exitosa de la API.
 */
function describeConversion(data, directionKey) {
    return directionKey === 'clpToUsd'
        ? { from: formatClp(data.amount_clp), to: formatUsd(data.amount_usd) }
        : { from: formatUsd(data.amount_usd), to: formatClp(data.amount_clp) };
}

function renderResult(data, directionKey) {
    if (!data.success) {
        el.result.textContent = '—';
        el.resultDetail.textContent = data.message;
        return;
    }

    const { from, to } = describeConversion(data, directionKey);
    el.result.textContent = to;
    el.resultDetail.textContent = `${from} equivale a ${to} · tasa ${formatClp(data.exchange_rate)}`;
}

function renderJson(status, data) {
    el.jsonStatus.textContent = `HTTP ${status}`;
    el.jsonOutput.textContent = JSON.stringify(data, null, 2);
}

// ---------------------------------------------------------------------------
// Notificación de error
// ---------------------------------------------------------------------------

function showToast(message) {
    clearTimeout(state.toastTimer);

    el.toastMessage.textContent = message;
    el.toast.classList.remove('opacity-0', 'translate-y-[-1rem]');

    state.toastTimer = setTimeout(() => {
        el.toast.classList.add('opacity-0', 'translate-y-[-1rem]');
    }, TOAST_DURATION_MS);
}

// ---------------------------------------------------------------------------
// Historial
// ---------------------------------------------------------------------------

function readHistory() {
    try {
        return JSON.parse(localStorage.getItem(HISTORY_KEY)) ?? [];
    } catch (error) {
        return [];
    }
}

function writeHistory(history) {
    try {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    } catch (error) {
        // Sin acceso a localStorage: el historial no se guarda.
    }
}

function saveToHistory(data, directionKey) {
    const entry = { ...describeConversion(data, directionKey), at: new Date().toISOString() };
    writeHistory([entry, ...readHistory()].slice(0, HISTORY_LIMIT));
    renderHistory();
}

function clearHistory() {
    writeHistory([]);
    renderHistory();
}

function renderHistory() {
    const history = readHistory();

    if (history.length === 0) {
        const empty = document.createElement('li');
        empty.className = 'py-3 text-sm text-slate-400 dark:text-slate-500';
        empty.textContent = 'Todavía no hay conversiones.';
        el.historyList.replaceChildren(empty);
        return;
    }

    el.historyList.replaceChildren(
        ...history.map((entry) => {
            const item = document.createElement('li');
            item.className = 'flex items-center justify-between gap-3 py-3 text-sm';

            const text = document.createElement('span');
            text.textContent = `${entry.from} → ${entry.to}`;

            const time = document.createElement('time');
            time.className = 'shrink-0 text-xs text-slate-400 dark:text-slate-500';
            time.dateTime = entry.at;
            time.textContent = new Date(entry.at).toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });

            item.append(text, time);
            return item;
        }),
    );
}

// ---------------------------------------------------------------------------
// Tema claro / oscuro
// ---------------------------------------------------------------------------

function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');

    try {
        localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    } catch (error) {
        // Sin acceso a localStorage: el tema no se recuerda.
    }
}

// ---------------------------------------------------------------------------
// Inicio
// ---------------------------------------------------------------------------

function init() {
    Object.assign(el, {
        toast: document.getElementById('toast'),
        toastMessage: document.getElementById('toast-message'),
        rateBanner: document.getElementById('rate-banner'),
        directionLabel: document.getElementById('direction-label'),
        amount: document.getElementById('amount'),
        amountPrefix: document.getElementById('amount-prefix'),
        amountHint: document.getElementById('amount-hint'),
        quickAmounts: document.getElementById('quick-amounts'),
        convertButton: document.getElementById('convert-button'),
        result: document.getElementById('result'),
        resultDetail: document.getElementById('result-detail'),
        jsonStatus: document.getElementById('json-status'),
        jsonOutput: document.getElementById('json-output'),
        historyList: document.getElementById('history-list'),
    });

    document.getElementById('convert-form').addEventListener('submit', convert);
    el.amount.addEventListener('input', () => setButton('idle'));
    document.getElementById('swap-button').addEventListener('click', toggleDirection);
    document.getElementById('theme-toggle').addEventListener('click', toggleTheme);
    document.getElementById('clear-history').addEventListener('click', clearHistory);

    renderDirection();
    renderHistory();
    loadRate();
}

document.addEventListener('DOMContentLoaded', init);
