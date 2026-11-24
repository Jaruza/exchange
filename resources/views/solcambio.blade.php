<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solcambio · CLP ↔ USD</title>

    {{-- Aplica el tema guardado antes de pintar la página para evitar el parpadeo. --}}
    <script>
        try {
            document.documentElement.classList.toggle('dark', localStorage.getItem('solcambio-theme') === 'dark');
        } catch (error) {
            // Sin acceso a localStorage: se usa el tema claro.
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#24103f] font-sans text-slate-800 antialiased transition-colors dark:bg-[#12071f] dark:text-violet-100">

    {{-- Notificación de error --}}
    <div id="toast" role="alert" aria-live="assertive"
         class="pointer-events-none fixed top-4 right-4 left-4 z-50 translate-y-[-1rem] rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 opacity-0 shadow-lg transition duration-300 sm:left-auto sm:max-w-sm dark:border-red-900 dark:bg-red-950 dark:text-red-200">
        <span id="toast-message"></span>
    </div>

    {{-- Anuncio con la tasa del día --}}
    <div class="bg-yellow-400 px-4 py-2 text-center text-sm font-extrabold tracking-[0.2em] text-[#24103f] uppercase">
        <span id="rate-banner">Cambio de hoy…</span>
    </div>

    <div class="mx-auto flex max-w-xl flex-col gap-6 px-4 py-8 sm:py-12">

        {{-- Encabezado --}}
        <header class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="size-8 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" fill="currentColor" />
                    <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                </svg>
                <span class="text-2xl font-bold tracking-tight text-white">Solcambio</span>
            </div>

            <button id="theme-toggle" type="button" aria-label="Cambiar tema"
                    class="rounded-lg border border-violet-400/30 p-2 text-violet-200 transition hover:bg-violet-400/15">
                {{-- Luna: se ve en modo claro --}}
                <svg class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                </svg>
                {{-- Sol: se ve en modo oscuro --}}
                <svg class="hidden size-5 text-yellow-400 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                    <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                </svg>
            </button>
        </header>

        {{-- Tarjeta conversora --}}
        <main class="rounded-2xl bg-white p-6 shadow-xl shadow-black/20 dark:bg-[#1d0e33]">
            <form id="convert-form" novalidate class="flex flex-col gap-5">

                <div class="flex items-center justify-between gap-3">
                    <span id="direction-label" class="text-sm font-semibold text-violet-700 dark:text-violet-300">De CLP a USD</span>
                    <button id="swap-button" type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-violet-300 hover:text-violet-700 dark:border-violet-900 dark:text-violet-200 dark:hover:border-violet-600">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 10h14l-4 -4" />
                            <path d="M17 14h-14l4 4" />
                        </svg>
                        Invertir
                    </button>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="amount" class="text-sm font-medium">Monto</label>
                    <div class="flex items-center rounded-xl border border-slate-200 bg-white focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 dark:border-violet-900 dark:bg-[#12071f]">
                        <span id="amount-prefix" class="pl-4 text-slate-400">$</span>
                        <input id="amount" name="amount" type="text" inputmode="decimal" autocomplete="off" placeholder="19000"
                               class="w-full bg-transparent px-2 py-3 text-lg outline-none placeholder:text-slate-300 dark:placeholder:text-violet-900">
                    </div>
                    <p id="amount-hint" class="text-xs text-slate-500 dark:text-violet-300/70">Solo pesos enteros, sin puntos ni comas.</p>
                </div>

                <div class="flex flex-col gap-2">
                    <span class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-violet-300/70">Montos rápidos</span>
                    <div id="quick-amounts" class="flex flex-wrap gap-2"></div>
                </div>

                <button id="convert-button" type="submit"
                        class="rounded-xl bg-violet-300 px-4 py-3 font-bold text-[#24103f] transition hover:bg-violet-200 disabled:cursor-not-allowed disabled:opacity-60">
                    Convertir
                </button>

                <div class="rounded-xl bg-violet-50 p-4 text-center dark:bg-[#12071f]">
                    <span class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-violet-300/70">Resultado</span>
                    <p id="result" class="mt-1 text-3xl font-semibold tracking-tight sm:text-4xl">—</p>
                    <p id="result-detail" class="mt-1 text-sm text-slate-500 dark:text-violet-300/70">Ingresa un monto y presiona Convertir.</p>
                </div>

                <details class="rounded-xl border border-slate-200 dark:border-violet-900">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-slate-600 select-none dark:text-violet-200">
                        Ver respuesta JSON
                        <span id="json-status" class="ml-2 rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-violet-950">sin datos</span>
                    </summary>
                    <pre id="json-output" class="overflow-x-auto border-t border-slate-200 px-4 py-3 font-mono text-xs dark:border-violet-900">Aún no hay respuestas.</pre>
                </details>
            </form>
        </main>

        {{-- Historial --}}
        <section class="rounded-2xl bg-white p-6 shadow-xl shadow-black/20 dark:bg-[#1d0e33]">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold">Historial</h2>
                <button id="clear-history" type="button"
                        class="text-sm font-medium text-slate-500 transition hover:text-red-600 dark:text-violet-300/70 dark:hover:text-red-400">
                    Limpiar
                </button>
            </div>
            <ul id="history-list" class="divide-y divide-slate-100 dark:divide-violet-950"></ul>
        </section>
    </div>
</body>
</html>
