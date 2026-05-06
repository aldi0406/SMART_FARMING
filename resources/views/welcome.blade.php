<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart Farming Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #f8f9fa;
            --panel: #e5e9eb;
            --card: #ffffff;
            --text: #2d3335;
            --muted: #5a6062;
            --primary: #2d6a4f;
            --light-primary: #e6ffee;
            --shadow-card: 0 8px 24px rgba(45, 51, 53, 0.04);
            --shadow-button: 0 20px 50px rgba(45, 106, 79, 0.3);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Inter", sans-serif;
        }

        .dashboard {
            width: 100%;
            max-width: 390px;
            margin: 0 auto;
            min-height: 100vh;
            background: var(--bg);
            padding: 16px 16px 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .title {
            margin: 0;
            color: var(--primary);
            font-size: 18px;
            line-height: 28px;
            font-weight: 700;
            letter-spacing: -0.45px;
            white-space: nowrap;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 9999px;
            object-fit: cover;
            border: 1px solid rgba(173, 179, 181, 0.3);
            box-shadow: 0 2px 8px rgba(45, 51, 53, 0.06);
        }

        .sync-note {
            margin: 0 0 12px;
            font-size: 11px;
            color: var(--muted);
        }

        .sync-note.error {
            color: #b91c1c;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .metric-card {
            background: var(--card);
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            min-height: 220px;
            padding: 16px;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            text-transform: uppercase;
            color: var(--muted);
            font-size: 9px;
            letter-spacing: 0.9px;
            font-weight: 700;
        }

        .metric-icon {
            width: 15px;
            height: 15px;
            color: var(--primary);
        }

        .gauge {
            margin: 4px auto 12px;
            width: 96px;
            height: 96px;
            border: 7px solid var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .gauge-value {
            display: block;
            font-size: 18px;
            line-height: 28px;
            font-weight: 800;
            color: var(--text);
        }

        .gauge-unit {
            display: block;
            margin-top: 3px;
            font-size: 7px;
            line-height: 10.5px;
            letter-spacing: 0.7px;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
        }

        .metric-footer {
            margin-top: auto;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        .controls {
            background: var(--panel);
            border-radius: 24px;
            margin-top: 24px;
            padding: 30px 26px 26px;
            text-align: center;
        }

        .controls h2 {
            margin: 0 0 22px;
            font-size: 20px;
            line-height: 28px;
            font-weight: 700;
            color: var(--text);
        }

        .mode-toggle {
            background: #fff;
            border-radius: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 6px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .mode-btn {
            border: 0;
            border-radius: 12px;
            background: transparent;
            padding: 12px;
            font-size: 14px;
            line-height: 20px;
            font-weight: 700;
            color: var(--muted);
            cursor: pointer;
            font-family: inherit;
            transition: 0.2s ease;
        }

        .mode-btn.active {
            background: var(--primary);
            color: var(--light-primary);
            box-shadow: 0 10px 15px -3px rgba(45, 106, 79, 0.2), 0 4px 6px -4px rgba(45, 106, 79, 0.2);
        }

        .pump-wrap {
            margin-top: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .pump-btn {
            width: 160px;
            height: 160px;
            border-radius: 9999px;
            border: 0;
            background: var(--primary);
            color: var(--light-primary);
            font-family: inherit;
            font-size: 18px;
            letter-spacing: 1.8px;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: var(--shadow-button);
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.2s ease;
        }

        .pump-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: 0 10px 24px rgba(45, 106, 79, 0.16);
        }

        .pump-label {
            margin: 0;
            font-size: 14px;
            line-height: 20px;
            font-weight: 500;
            color: var(--muted);
        }

        /* ===== AI CHATBOT WIDGET ===== */
        .ai-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: #fff;
            box-shadow: 0 6px 28px rgba(45,106,79,0.45);
            cursor: pointer;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1), box-shadow 0.3s ease;
        }
        .ai-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 36px rgba(45,106,79,0.55);
        }
        .ai-fab.hidden { display: none; }
        .ai-fab svg { width: 28px; height: 28px; }

        .ai-chat-panel {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 560px;
            max-height: calc(100vh - 48px);
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px) saturate(1.6);
            -webkit-backdrop-filter: blur(20px) saturate(1.6);
            border-radius: 24px;
            box-shadow: 0 16px 64px rgba(0,0,0,0.14), 0 0 0 1px rgba(45,106,79,0.08);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            pointer-events: none;
            transition: opacity 0.35s cubic-bezier(.4,0,.2,1), transform 0.35s cubic-bezier(.4,0,.2,1);
        }
        .ai-chat-panel.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .ai-chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: #fff;
            flex-shrink: 0;
        }
        .ai-chat-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ai-chat-header-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
        }
        .ai-chat-header-avatar svg { width: 20px; height: 20px; }
        .ai-chat-header-info h3 {
            margin: 0; font-size: 15px; font-weight: 700;
        }
        .ai-chat-header-info p {
            margin: 2px 0 0; font-size: 11px; opacity: 0.8;
        }
        .ai-chat-close {
            background: rgba(255,255,255,0.15);
            border: none; border-radius: 50%;
            width: 32px; height: 32px;
            cursor: pointer; color: #fff;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .ai-chat-close:hover { background: rgba(255,255,255,0.3); }
        .ai-chat-close svg { width: 18px; height: 18px; }

        .ai-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .ai-chat-messages::-webkit-scrollbar { width: 4px; }
        .ai-chat-messages::-webkit-scrollbar-track { background: transparent; }
        .ai-chat-messages::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

        .ai-msg {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 16px;
            font-size: 13.5px;
            line-height: 1.5;
            word-break: break-word;
            animation: msgFadeIn 0.3s ease;
        }
        @keyframes msgFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .ai-msg.user {
            align-self: flex-end;
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .ai-msg.bot {
            align-self: flex-start;
            background: #f0f4f1;
            color: var(--text);
            border-bottom-left-radius: 4px;
        }
        .ai-msg.bot .msg-label,
        .ai-msg.user .msg-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            opacity: 0.7;
        }
        .ai-msg.system {
            align-self: center;
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
            padding: 4px 8px;
        }

        .ai-typing {
            display: flex;
            gap: 4px;
            padding: 12px 14px;
            align-self: flex-start;
            background: #f0f4f1;
            border-radius: 16px;
            border-bottom-left-radius: 4px;
        }
        .ai-typing span {
            width: 7px; height: 7px;
            background: #40916c;
            border-radius: 50%;
            animation: typingDot 1.4s infinite ease-in-out;
        }
        .ai-typing span:nth-child(2) { animation-delay: 0.2s; }
        .ai-typing span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingDot {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-6px); opacity: 1; }
        }

        .ai-chat-input-area {
            padding: 12px 16px 16px;
            border-top: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: flex-end;
            gap: 8px;
            flex-shrink: 0;
            background: rgba(255,255,255,0.6);
        }
        .ai-chat-input {
            flex: 1;
            border: 1.5px solid #dde2df;
            border-radius: 14px;
            padding: 10px 14px;
            font-size: 13.5px;
            font-family: inherit;
            background: #fff;
            resize: none;
            min-height: 40px;
            max-height: 100px;
            outline: none;
            transition: border-color 0.2s;
        }
        .ai-chat-input:focus {
            border-color: #40916c;
        }
        .ai-chat-input::placeholder {
            color: #adb3b5;
        }

        .ai-btn-icon {
            width: 40px; height: 40px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.15s;
            flex-shrink: 0;
        }
        .ai-btn-icon:active { transform: scale(0.92); }

        .ai-btn-send {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: #fff;
        }
        .ai-btn-send:hover { background: linear-gradient(135deg, #1b4332, #2d6a4f); }
        .ai-btn-send:disabled { opacity: 0.5; cursor: not-allowed; }
        .ai-btn-send svg { width: 18px; height: 18px; }

        .ai-btn-voice {
            background: #f0f4f1;
            color: #2d6a4f;
        }
        .ai-btn-voice:hover { background: #dde8df; }
        .ai-btn-voice svg { width: 20px; height: 20px; }
        .ai-btn-voice.recording {
            background: #ef4444;
            color: #fff;
            animation: pulseRecord 1.5s infinite;
        }
        @keyframes pulseRecord {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
            50% { box-shadow: 0 0 0 10px rgba(239,68,68,0); }
        }

        .ai-voice-status {
            text-align: center;
            font-size: 11px;
            color: var(--muted);
            padding: 0 16px 8px;
        }
        .ai-voice-status.recording-text { color: #ef4444; font-weight: 600; }
    </style>
</head>

<body>
    <main class="dashboard" data-node-id="1:2">
        <section class="topbar" data-node-id="1:7">
            <h1 class="title">Smart Farming Dashboard</h1>
            <img class="avatar" src="https://www.figma.com/api/mcp/asset/a4dccd33-4fa7-4520-82fc-882c358f9691"
                alt="Profil pengguna">
        </section>
        <p id="sync-note" class="sync-note">Menyambungkan data realtime...</p>

        <section class="grid" data-node-id="1:18">
            <article class="metric-card" data-node-id="1:19">
                <div class="metric-header">
                    <span>Kelembapan tanah</span>
                    <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2c0 3.5 2 6 5 8.5A6.5 6.5 0 1 1 8 14"></path>
                        <path d="M16 18l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="gauge">
                    <div>
                        <span id="soil-value" class="gauge-value">--%</span>
                        <span id="soil-unit" class="gauge-unit">-</span>
                    </div>
                </div>
                <p id="soil-footer" class="metric-footer">-</p>
            </article>

            <article class="metric-card" data-node-id="1:36">
                <div class="metric-header">
                    <span>Suhu udara</span>
                    <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 14.76V3.5a2 2 0 1 0-4 0v11.26a4 4 0 1 0 4 0z"></path>
                        <line x1="10" y1="11" x2="14" y2="11"></line>
                    </svg>
                </div>
                <div class="gauge">
                    <div>
                        <span id="temp-value" class="gauge-value">--&deg;</span>
                        <span id="temp-unit" class="gauge-unit">-</span>
                    </div>
                </div>
                <p id="temp-footer" class="metric-footer">-</p>
            </article>

            <article class="metric-card" data-node-id="1:53">
                <div class="metric-header">
                    <span>Kelembapan udara</span>
                    <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3c4 5 6 8 6 11a6 6 0 0 1-12 0c0-3 2-6 6-11z"></path>
                        <line x1="10" y1="16" x2="14" y2="12"></line>
                    </svg>
                </div>
                <div class="gauge">
                    <div>
                        <span id="hum-value" class="gauge-value">--%</span>
                        <span id="hum-unit" class="gauge-unit">-</span>
                    </div>
                </div>
                <p id="hum-footer" class="metric-footer">-</p>
            </article>

            <article class="metric-card" data-node-id="1:70">
                <div class="metric-header">
                    <span>Status pompa</span>
                    <svg class="metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3c4 5 6 8 6 11a6 6 0 0 1-12 0c0-3 2-6 6-11z"></path>
                    </svg>
                </div>
                <div class="gauge">
                    <div>
                        <span id="pump-value" class="gauge-value">OFF</span>
                        <span id="pump-unit" class="gauge-unit">IDLE</span>
                    </div>
                </div>
                <p id="pump-footer" class="metric-footer">Mati</p>
            </article>
        </section>

        <section class="controls" data-node-id="1:88">
            <h2>Mode Pompa</h2>

            <div class="mode-toggle" data-node-id="1:93">
                <button id="btn-manual" class="mode-btn" type="button">Manual</button>
                <button id="btn-auto" class="mode-btn active" type="button">Otomatis</button>
            </div>

            <div class="pump-wrap" data-node-id="1:99">
                <button id="btn-siram" class="pump-btn" type="button" aria-label="Tombol siram" disabled>
                    <svg width="32" height="40" viewBox="0 0 24 30" fill="none"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 1C7 7 3 11 3 16a9 9 0 0 0 18 0c0-5-4-9-9-15Z" fill="#CFF2DD" />
                        <path d="M7.5 19.8c.8 1.8 2.1 2.7 4.5 3" stroke="#2D6A4F" stroke-width="2"
                            stroke-linecap="round" />
                    </svg>
                    SIRAM
                </button>
                <p id="pump-label" class="pump-label">Mode otomatis aktif</p>
            </div>
        </section>
    </main>

    <script src="https://www.gstatic.com/firebasejs/9.22.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.1/firebase-database-compat.js"></script>
    <script>
        const refreshMs = Number(@json(config('smart_farming.dashboard.refresh_interval_seconds', 1) * 1000));
        const FIREBASE_BASE = @json(config(
                'smart_farming.firebase_base',
                env('FIREBASE_BASE', 'https://smart-farming-90ebf-default-rtdb.firebaseio.com')));
        const DEVICE_UID = @json(config('smart_farming.dashboard.default_device_uid', env('SMART_FARMING_DEVICE_UID', 'ESP32-01')));

        const el = {
            syncNote: document.getElementById('sync-note'),
            soilValue: document.getElementById('soil-value'),
            soilUnit: document.getElementById('soil-unit'),
            soilFooter: document.getElementById('soil-footer'),
            tempValue: document.getElementById('temp-value'),
            tempUnit: document.getElementById('temp-unit'),
            tempFooter: document.getElementById('temp-footer'),
            humValue: document.getElementById('hum-value'),
            humUnit: document.getElementById('hum-unit'),
            humFooter: document.getElementById('hum-footer'),
            pumpValue: document.getElementById('pump-value'),
            pumpUnit: document.getElementById('pump-unit'),
            pumpFooter: document.getElementById('pump-footer'),
            pumpLabel: document.getElementById('pump-label'),
            btnManual: document.getElementById('btn-manual'),
            btnAuto: document.getElementById('btn-auto'),
            btnSiram: document.getElementById('btn-siram'),
        };

        let currentMode = 'automatic';
        let currentPumpOn = false;
        let busy = false;
        let fallbackPollingId = null;

        function setSyncMessage(text, isError = false) {
            el.syncNote.textContent = text;
            el.syncNote.classList.toggle('error', isError);
        }

        function asNumber(value) {
            const n = Number(value);
            return Number.isFinite(n) ? n : null;
        }

        function humanizeStatus(status) {
            const value = String(status || '').toLowerCase();
            if (value === 'optimum') return 'Lembab';
            if (value === 'moderate') return 'Normal';
            if (value === 'normal') return 'Normal';
            if (value === 'low') return 'Kering';
            if (value === 'high') return 'Tinggi';
            return '-';
        }

        function latestTelemetry(node) {
            if (!node || typeof node !== 'object') return {};

            const keys = Object.keys(node);
            if (keys.length === 0) return {};

            const looksLikeTelemetry = ['soil_moisture', 'air_temperature', 'air_humidity', 'pump_status']
                .some((key) => Object.prototype.hasOwnProperty.call(node, key));

            if (looksLikeTelemetry) {
                return node;
            }

            const lastKey = keys[keys.length - 1];
            const lastValue = node[lastKey];
            return (lastValue && typeof lastValue === 'object') ? lastValue : {};
        }

        function render(data) {
            const soil = asNumber(data.soil_moisture);
            const temp = asNumber(data.air_temperature);
            const hum = asNumber(data.air_humidity);

            el.soilValue.textContent = soil === null ? '--%' : `${Math.round(soil)}%`;
            el.soilUnit.textContent = String(data.soil_status || '-').toUpperCase();
            el.soilFooter.textContent = humanizeStatus(data.soil_status);

            el.tempValue.innerHTML = temp === null ? '--&deg;' : `${temp.toFixed(1)}&deg;`;
            el.tempUnit.textContent = 'CELSIUS';
            el.tempFooter.textContent = humanizeStatus(data.temperature_status);

            el.humValue.textContent = hum === null ? '--%' : `${Math.round(hum)}%`;
            el.humUnit.textContent = String(data.humidity_status || '-').toUpperCase();
            el.humFooter.textContent = humanizeStatus(data.humidity_status);

            currentMode = data.pump_mode || 'automatic';
            currentPumpOn = Boolean(data.pump_state);

            el.pumpValue.textContent = currentPumpOn ? 'ON' : 'OFF';
            el.pumpUnit.textContent = currentPumpOn ? 'RUNNING' : 'IDLE';
            el.pumpFooter.textContent = currentPumpOn ? 'Menyala' : 'Mati';

            el.btnManual.classList.toggle('active', currentMode === 'manual');
            el.btnAuto.classList.toggle('active', currentMode === 'automatic');

            const manualMode = currentMode === 'manual';
            el.btnSiram.disabled = !manualMode || busy;

            if (manualMode) {
                el.pumpLabel.textContent = currentPumpOn ? 'Pompa menyala (manual)' : 'Pompa siap (manual)';
            } else {
                el.pumpLabel.textContent = 'Mode otomatis aktif';
            }
        }

        async function requestJson(url, options = {}) {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    ...(options.method && options.method !== 'GET' ? {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    } : {}),
                    ...(options.headers || {}),
                },
                credentials: 'same-origin',
                ...options,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message || `Request gagal (${response.status})`;
                const error = new Error(message);
                error.status = response.status;
                throw error;
            }

            return payload;
        }

        async function loadLiveData() {
            try {
                const [telemetryResp, cmdResp, baseResp] = await Promise.all([
                    fetch(`${FIREBASE_BASE}/smart_farming/telemetry/${DEVICE_UID}.json`),
                    fetch(`${FIREBASE_BASE}/smart_farming/commands/${DEVICE_UID}.json`),
                    fetch(`${FIREBASE_BASE}/smart_farming.json`),
                ]);

                const telemetryJson = await telemetryResp.json();
                const cmdJson = await cmdResp.json();
                const baseJson = await baseResp.json();

                const latest = latestTelemetry(telemetryJson);
                const legacy = baseJson && typeof baseJson === 'object' ? baseJson : {};

                const pumpMode = cmdJson?.pump_mode ?? legacy.pump_mode ?? 'automatic';
                const telemetryPump = latest.pump_status ?? latest.pump ?? legacy.pump_status ?? legacy.pump_state ??
                    legacy.pump ?? false;
                const commandPump = cmdJson?.pump_state ?? false;

                const payload = {
                    soil_moisture: latest.soil_moisture ?? latest.soil ?? legacy.soil_moisture ?? legacy.soil ??
                        null,
                    air_temperature: latest.air_temperature ?? latest.temperature ?? legacy.air_temperature ??
                        legacy.temperature ?? null,
                    air_humidity: latest.air_humidity ?? latest.humidity ?? legacy.air_humidity ?? legacy
                        .humidity ?? null,
                    soil_status: latest.soil_status ?? legacy.soil_status ?? null,
                    temperature_status: latest.temperature_status ?? legacy.temperature_status ?? null,
                    humidity_status: latest.humidity_status ?? legacy.humidity_status ?? null,
                    pump_mode: pumpMode,
                    pump_state: pumpMode === 'manual' ? commandPump : telemetryPump,
                    recorded_at: latest.recorded_at ?? latest.ts ?? legacy.recorded_at ?? null,
                };

                render(payload);
                setSyncMessage(`Realtime aktif via Firebase (${new Date().toLocaleTimeString('id-ID')})`);
            } catch (error) {
                setSyncMessage(error.message || 'Gagal mengambil data realtime.', true);
            }
        }

        function startFallbackPolling() {
            if (fallbackPollingId !== null) {
                return;
            }

            fallbackPollingId = setInterval(loadLiveData, refreshMs);
        }

        function stopFallbackPolling() {
            if (fallbackPollingId === null) {
                return;
            }

            clearInterval(fallbackPollingId);
            fallbackPollingId = null;
        }

        function initRealtime() {
            // We rely on Firebase REST polling for now; start polling loop
            startFallbackPolling();
        }

        async function updateMode(mode) {
            if (busy || currentMode === mode) return;

            busy = true;
            el.btnSiram.disabled = true;
            setSyncMessage('Mengubah mode pompa...');

            try {
                // Write command object to Firebase
                const cmd = {
                    pump_mode: mode,
                    pump_state: currentPumpOn,
                    last_changed_at: new Date().toISOString()
                };
                const url = `${FIREBASE_BASE}/smart_farming/commands/${DEVICE_UID}.json`;
                await fetch(url, {
                    method: 'PUT',
                    body: JSON.stringify(cmd)
                });

                // Also write pump_state node and log
                const stateUrl = `${FIREBASE_BASE}/smart_farming/pump_state/${DEVICE_UID}.json`;
                await fetch(stateUrl, {
                    method: 'PUT',
                    body: JSON.stringify({
                        is_on: currentPumpOn,
                        mode: mode,
                        last_changed_at: new Date().toISOString()
                    })
                });

                await fetch(`${FIREBASE_BASE}/smart_farming/pump_logs/${DEVICE_UID}.json`, {
                    method: 'POST',
                    body: JSON.stringify({
                        source: 'dashboard',
                        action: 'mode_changed',
                        mode,
                        at: new Date().toISOString()
                    })
                });

                await loadLiveData();
            } catch (error) {
                setSyncMessage(error.message || 'Gagal mengubah mode.', true);
            } finally {
                busy = false;
                el.btnSiram.disabled = currentMode !== 'manual';
            }
        }

        async function toggleSiram() {
            if (busy || currentMode !== 'manual') return;

            busy = true;
            el.btnSiram.disabled = true;
            setSyncMessage('Mengirim perintah pompa...');

            const newState = !currentPumpOn;

            try {
                // write command and pump_state
                const cmd = {
                    pump_mode: 'manual',
                    pump_state: newState,
                    last_changed_at: new Date().toISOString()
                };
                const url = `${FIREBASE_BASE}/smart_farming/commands/${DEVICE_UID}.json`;
                await fetch(url, {
                    method: 'PUT',
                    body: JSON.stringify(cmd)
                });

                const stateUrl = `${FIREBASE_BASE}/smart_farming/pump_state/${DEVICE_UID}.json`;
                await fetch(stateUrl, {
                    method: 'PUT',
                    body: JSON.stringify({
                        is_on: newState,
                        mode: 'manual',
                        last_changed_at: new Date().toISOString()
                    })
                });

                await fetch(`${FIREBASE_BASE}/smart_farming/pump_logs/${DEVICE_UID}.json`, {
                    method: 'POST',
                    body: JSON.stringify({
                        source: 'dashboard',
                        action: newState ? 'switched_on' : 'switched_off',
                        at: new Date().toISOString()
                    })
                });

                await loadLiveData();
            } catch (error) {
                setSyncMessage(error.message || 'Perintah siram gagal.', true);
            } finally {
                busy = false;
                el.btnSiram.disabled = currentMode !== 'manual';
            }
        }

        el.btnManual.addEventListener('click', () => updateMode('manual'));
        el.btnAuto.addEventListener('click', () => updateMode('automatic'));
        el.btnSiram.addEventListener('click', toggleSiram);

        loadLiveData();
        startFallbackPolling();
        initRealtime();
    </script>

    <!-- ===== AI CHATBOT WIDGET HTML ===== -->
    <!-- Floating Action Button -->
    <button id="ai-fab" class="ai-fab" aria-label="Buka AI Assistant">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
    </button>

    <!-- Chat Panel -->
    <div id="ai-chat-panel" class="ai-chat-panel" role="dialog" aria-label="AI Smart Farming Assistant">
        <!-- Header -->
        <div class="ai-chat-header">
            <div class="ai-chat-header-left">
                <div class="ai-chat-header-avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
                    </svg>
                </div>
                <div class="ai-chat-header-info">
                    <h3>AI Smart Farming</h3>
                    <p>Asisten pertanian cerdas Anda</p>
                </div>
            </div>
            <button id="ai-chat-close" class="ai-chat-close" aria-label="Tutup chat">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Messages -->
        <div id="ai-messages" class="ai-chat-messages">
            <div class="ai-msg bot">
                <div class="msg-label">AI Assistant</div>
                Halo! Saya asisten AI untuk Smart Farming Anda 🌱 Tanya apa saja tentang kondisi tanaman, kelembapan, suhu, atau cara merawat tanaman Anda!
            </div>
        </div>

        <!-- Voice Status -->
        <div id="ai-voice-status" class="ai-voice-status" style="display:none;"></div>

        <!-- Input Area -->
        <div class="ai-chat-input-area">
            <textarea id="ai-chat-input" class="ai-chat-input" placeholder="Ketik pesan..." rows="1" aria-label="Pesan chat"></textarea>
            <button id="ai-btn-voice" class="ai-btn-icon ai-btn-voice" aria-label="Rekam suara">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
            </button>
            <button id="ai-btn-send" class="ai-btn-icon ai-btn-send" aria-label="Kirim pesan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
        // ===== AI CHATBOT JAVASCRIPT =====
        const aiFab        = document.getElementById('ai-fab');
        const aiPanel      = document.getElementById('ai-chat-panel');
        const aiClose      = document.getElementById('ai-chat-close');
        const aiMessages   = document.getElementById('ai-messages');
        const aiInput      = document.getElementById('ai-chat-input');
        const aiBtnSend    = document.getElementById('ai-btn-send');
        const aiBtnVoice   = document.getElementById('ai-btn-voice');
        const aiVoiceStat  = document.getElementById('ai-voice-status');
        const csrfToken    = document.querySelector('meta[name="csrf-token"]').content;

        let aiIsOpen       = false;
        let aiIsBusy       = false;
        let mediaRecorder  = null;
        let audioChunks    = [];
        let isRecording    = false;

        // Toggle panel
        function toggleAiPanel() {
            aiIsOpen = !aiIsOpen;
            aiPanel.classList.toggle('open', aiIsOpen);
            aiFab.classList.toggle('hidden', aiIsOpen);
            if (aiIsOpen) { aiInput.focus(); scrollToBottom(); }
        }

        aiFab.addEventListener('click', toggleAiPanel);
        aiClose.addEventListener('click', toggleAiPanel);

        function scrollToBottom() {
            aiMessages.scrollTop = aiMessages.scrollHeight;
        }

        function addMessage(text, role) {
            const div = document.createElement('div');
            div.className = `ai-msg ${role}`;
            if (role !== 'system') {
                const label = document.createElement('div');
                label.className = 'msg-label';
                label.textContent = role === 'user' ? 'Anda' : 'AI Assistant';
                div.appendChild(label);
            }
            const content = document.createElement('span');
            content.textContent = text;
            div.appendChild(content);
            aiMessages.appendChild(div);
            scrollToBottom();
        }

        function showTyping() {
            const div = document.createElement('div');
            div.className = 'ai-typing';
            div.id = 'ai-typing-indicator';
            div.innerHTML = '<span></span><span></span><span></span>';
            aiMessages.appendChild(div);
            scrollToBottom();
            return div;
        }

        function removeTyping() {
            const t = document.getElementById('ai-typing-indicator');
            if (t) t.remove();
        }

        // Send text message
        async function sendChatMessage() {
            const msg = aiInput.value.trim();
            if (!msg || aiIsBusy) return;

            aiIsBusy = true;
            aiBtnSend.disabled = true;
            addMessage(msg, 'user');
            aiInput.value = '';
            aiInput.style.height = 'auto';
            const typing = showTyping();

            try {
                const res = await fetch('/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: msg }),
                });
                const data = await res.json();
                removeTyping();
                if (data.reply) {
                    addMessage(data.reply, 'bot');
                    speakText(data.reply);
                } else {
                    addMessage('Maaf, terjadi kesalahan. Coba lagi.', 'system');
                }
            } catch (e) {
                removeTyping();
                addMessage('Gagal terhubung ke AI. Periksa koneksi Anda.', 'system');
            } finally {
                aiIsBusy = false;
                aiBtnSend.disabled = false;
            }
        }

        aiBtnSend.addEventListener('click', sendChatMessage);
        aiInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); }
        });
        aiInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // ===== WEB SPEECH API (NATIVE VOICE STT & TTS) =====
        let recognition = null;
        let isSpeaking = false;
        const synth = window.speechSynthesis;

        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'id-ID'; // Bahasa Indonesia

            recognition.onstart = () => {
                isRecording = true;
                aiIsBusy = true;
                aiBtnVoice.classList.add('recording');
                aiVoiceStat.style.display = 'block';
                aiVoiceStat.className = 'ai-voice-status recording-text';
                aiVoiceStat.textContent = '🎙️ Mendengarkan... Silakan bicara.';
                
                // Matikan suara AI jika sedang berbicara saat user mau bicara
                if (synth && synth.speaking) {
                    synth.cancel();
                }
            };

            recognition.onresult = (event) => {
                const speechToText = event.results[0][0].transcript;
                aiInput.value = speechToText;
                sendChatMessage();
            };

            recognition.onerror = (event) => {
                console.error('Speech Recognition Error:', event.error);
                if (event.error === 'not-allowed') {
                    addMessage('Izin mikrofon ditolak. Periksa pengaturan browser Anda.', 'system');
                } else {
                    addMessage('Gagal mengenali suara. Coba bicara lebih jelas.', 'system');
                }
            };

            recognition.onend = () => {
                isRecording = false;
                aiIsBusy = false;
                aiBtnVoice.classList.remove('recording');
                aiVoiceStat.style.display = 'none';
            };
        }

        // Jalankan voice recording atau stop AI suara
        aiBtnVoice.addEventListener('click', () => {
            if (synth && synth.speaking) {
                synth.cancel();
                return;
            }

            if (!recognition) {
                addMessage('Browser Anda tidak mendukung fitur suara (STT). Gunakan Google Chrome/Microsoft Edge.', 'system');
                return;
            }

            if (isRecording) {
                recognition.stop();
            } else {
                recognition.start();
            }
        });

        // Fungsi Text-To-Speech (Membaca Teks Jawaban)
        function speakText(text) {
            if (!synth) return;

            // Batalkan suara yang sedang berjalan jika ada
            if (synth.speaking) {
                synth.cancel();
            }

            // Hilangkan emoji dan teks khusus agar dibaca lebih natural
            const cleanText = text
                .replace(/[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF]/g, "")
                .replace(/\*+/g, ""); // Hilangkan format markdown bold

            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'id-ID'; // Bahasa Indonesia
            utterance.rate = 1.0;     // Kecepatan membaca
            utterance.pitch = 1.0;    // Nada suara

            // Pilih suara Bahasa Indonesia jika tersedia
            const voices = synth.getVoices();
            const indVoice = voices.find(v => v.lang.startsWith('id') || v.lang.includes('Indonesia'));
            if (indVoice) {
                utterance.voice = indVoice;
            }

            synth.speak(utterance);
        }

        // Chrome memuat suara secara async, picu pemuatan getVoices
        if (synth && synth.onvoiceschanged !== undefined) {
            synth.onvoiceschanged = () => { synth.getVoices(); };
        }
    </script>
</body>

</html>
