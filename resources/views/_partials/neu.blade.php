{{-- Shared neumorphic theme for admin & artist dashboards --}}
<style>
    :root {
        --neu-bg: #e8edf4;
        --neu-soft: #dde3ee;
        --neu-shadow-dark: rgba(163, 177, 198, 0.65);
        --neu-shadow-light: rgba(255, 255, 255, 0.95);
        --neu-text: #4b5a72;
        --neu-text-strong: #2d3a52;
        --neu-accent: #6c63ff;
        --neu-accent-soft: #eceaff;
    }

    * {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        -webkit-tap-highlight-color: transparent;
    }

    body {
        background: var(--neu-bg);
        color: var(--neu-text);
    }

    /* ── Neumorphic primitives ── */
    .neu {
        background: var(--neu-bg);
        border-radius: 24px;
        box-shadow: 10px 10px 24px var(--neu-shadow-dark), -10px -10px 24px var(--neu-shadow-light);
    }

    .neu-sm {
        background: var(--neu-bg);
        border-radius: 18px;
        box-shadow: 6px 6px 14px var(--neu-shadow-dark), -6px -6px 14px var(--neu-shadow-light);
    }

    .neu-inset {
        background: var(--neu-bg);
        border-radius: 18px;
        box-shadow: inset 5px 5px 12px var(--neu-shadow-dark), inset -5px -5px 12px var(--neu-shadow-light);
    }

    .neu-btn {
        background: var(--neu-bg);
        border-radius: 999px;
        box-shadow: 5px 5px 12px var(--neu-shadow-dark), -5px -5px 12px var(--neu-shadow-light);
        transition: all .2s ease;
        cursor: pointer;
    }

    .neu-btn:hover {
        transform: translateY(-2px);
    }

    .neu-btn:active {
        transform: translateY(0);
        box-shadow: inset 4px 4px 10px var(--neu-shadow-dark), inset -4px -4px 10px var(--neu-shadow-light);
    }

    .neu-accent {
        background: linear-gradient(145deg, #7a70ff, #5b52e8);
        border-radius: 999px;
        box-shadow: 5px 5px 12px rgba(108, 99, 255, .45), -5px -5px 12px var(--neu-shadow-light);
        transition: all .2s ease;
        cursor: pointer;
    }

    .neu-accent:hover {
        transform: translateY(-2px);
        box-shadow: 7px 7px 16px rgba(108, 99, 255, .5), -7px -7px 16px var(--neu-shadow-light);
    }

    .neu-accent:active {
        transform: translateY(0);
        box-shadow: inset 4px 4px 10px rgba(40, 30, 140, .35), inset -4px -4px 10px rgba(255, 255, 255, .25);
    }

    .neu-circle {
        background: var(--neu-bg);
        border-radius: 50%;
        box-shadow: 4px 4px 10px var(--neu-shadow-dark), -4px -4px 10px var(--neu-shadow-light);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .neu-chip {
        background: var(--neu-bg);
        border-radius: 999px;
        box-shadow: inset 3px 3px 7px var(--neu-shadow-dark), inset -3px -3px 7px var(--neu-shadow-light);
        padding: .25rem .75rem;
        font-size: .75rem;
        font-weight: 700;
    }

    .neu-input {
        background: var(--neu-bg);
        border-radius: 16px;
        box-shadow: inset 4px 4px 10px var(--neu-shadow-dark), inset -4px -4px 10px var(--neu-shadow-light);
        padding: .75rem 1rem;
        font-size: .875rem;
        color: var(--neu-text-strong);
        outline: none;
        width: 100%;
        border: none;
        transition: box-shadow .2s ease;
    }

    .neu-input::placeholder {
        color: rgba(75, 90, 114, .5);
    }

    .neu-input:focus {
        box-shadow: inset 4px 4px 10px var(--neu-shadow-dark), inset -4px -4px 10px var(--neu-shadow-light), 0 0 0 3px rgba(108, 99, 255, .25);
    }

    .text-gradient {
        background: linear-gradient(120deg, #6c63ff, #a855f7, #ec4899);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* ── Compatibility layer: remaps legacy dark-theme cards to neumorphic ── */
    main .bg-gray-900,
    main .bg-gray-800 {
        background: var(--neu-bg) !important;
        box-shadow: 8px 8px 18px var(--neu-shadow-dark), -8px -8px 18px var(--neu-shadow-light);
    }

    main .rounded-2xl {
        border-radius: 22px;
    }

    main .border-white\/10,
    main .border-white\/5,
    main .border-white\/20,
    main .border-white\/30 {
        border-color: transparent !important;
    }

    main .border-dashed {
        border-color: rgba(75, 90, 114, .3) !important;
    }

    main .border-gray-700 {
        border-color: rgba(75, 90, 114, .2) !important;
    }

    main .bg-white\/5 {
        background: rgba(75, 90, 114, .07) !important;
    }

    main .bg-white\/20 {
        background: rgba(75, 90, 114, .15) !important;
    }

    main .hover\:bg-white\/5:hover,
    main .hover\:bg-white\/10:hover {
        background: rgba(75, 90, 114, .07) !important;
    }

    main .divide-white\/5 > :not([hidden]) ~ :not([hidden]) {
        border-color: rgba(75, 90, 114, .1) !important;
    }

    main .bg-green-500\/10 { background: rgba(22, 163, 74, .12) !important; }
    main .bg-amber-500\/10 { background: rgba(217, 119, 6, .12) !important; }
    main .bg-red-500\/10 { background: rgba(220, 38, 38, .12) !important; }
    main .bg-blue-500\/10 { background: rgba(37, 99, 235, .12) !important; }
    main .bg-purple-500\/10 { background: rgba(108, 99, 255, .12) !important; }

    /* ── Text remaps for light background ── */
    main .text-gray-100,
    main .text-gray-200,
    main .text-gray-300 { color: var(--neu-text-strong) !important; }
    main .text-gray-400 { color: var(--neu-text) !important; }
    main .text-gray-500 { color: rgba(75, 90, 114, .8) !important; }
    main .text-gray-600 { color: rgba(75, 90, 114, .6) !important; }

    main .text-purple-400 { color: var(--neu-accent) !important; }
    main .text-fuchsia-400 { color: #d946ef !important; }
    main .text-green-400 { color: #16a34a !important; }
    main .text-emerald-400 { color: #059669 !important; }
    main .text-amber-400 { color: #d97706 !important; }
    main .text-orange-400 { color: #ea580c !important; }
    main .text-red-400 { color: #dc2626 !important; }
    main .text-blue-400 { color: #2563eb !important; }
    main .text-cyan-400 { color: #0891b2 !important; }
    main .text-yellow-400 { color: #ca8a04 !important; }
    main .text-slate-400 { color: var(--neu-text) !important; }

    main .text-purple-300 { color: var(--neu-accent) !important; }
    main .text-green-300 { color: #15803d !important; }
    main .text-amber-300 { color: #b45309 !important; }
    main .text-red-300 { color: #b91c1c !important; }
    main .text-blue-300 { color: #1d4ed8 !important; }

    main input.text-white,
    main select.text-white,
    main textarea.text-white { color: var(--neu-text-strong) !important; }

    main table tbody tr:hover { background: rgba(75, 90, 114, .05) !important; }
</style>