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

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        const refreshMs = Number(@json(($refreshIntervalSeconds ?? 1) * 1000));
        const reverb = @json($reverb ?? []);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
        let wsConnected = false;

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
                const payload = await requestJson('/dashboard/live');
                render(payload.data || {});
                const source = wsConnected ? 'WebSocket' : 'Polling fallback';
                setSyncMessage(`Realtime aktif via ${source} (${new Date().toLocaleTimeString('id-ID')})`);
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

        function initReverbRealtime() {
            const hasConfig = reverb && reverb.key && reverb.host && reverb.port;

            if (!hasConfig || !window.Pusher) {
                setSyncMessage('Konfigurasi Reverb belum lengkap, memakai polling fallback.', true);
                startFallbackPolling();
                return;
            }

            const forceTLS = String(reverb.scheme || 'http').toLowerCase() === 'https';
            const configHost = String(reverb.host || '').toLowerCase();
            const wsHost = (configHost === 'localhost' || configHost === '127.0.0.1') ?
                window.location.hostname :
                reverb.host;

            const pusher = new window.Pusher(reverb.key, {
                wsHost,
                wsPort: Number(reverb.port),
                wssPort: Number(reverb.port),
                forceTLS,
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: 'mt1',
            });

            pusher.connection.bind('connected', () => {
                wsConnected = true;
                stopFallbackPolling();
                setSyncMessage(`Realtime aktif via WebSocket (${new Date().toLocaleTimeString('id-ID')})`);
            });

            pusher.connection.bind('disconnected', () => {
                wsConnected = false;
                setSyncMessage('Koneksi WebSocket terputus, fallback polling aktif.', true);
                startFallbackPolling();
            });

            pusher.connection.bind('error', () => {
                wsConnected = false;
                setSyncMessage('Koneksi WebSocket gagal, fallback polling aktif.', true);
                startFallbackPolling();
            });

            const channel = pusher.subscribe(reverb.channel || 'smart-farming.dashboard');
            channel.bind(reverb.event || 'dashboard.updated', (payload) => {
                const data = payload?.data || payload || {};
                render(data);
                setSyncMessage(`Update realtime diterima (${new Date().toLocaleTimeString('id-ID')})`);
            });
        }

        async function updateMode(mode) {
            if (busy || currentMode === mode) return;

            busy = true;
            el.btnSiram.disabled = true;
            setSyncMessage('Mengubah mode pompa...');

            try {
                await requestJson('/dashboard/mode', {
                    method: 'POST',
                    body: JSON.stringify({
                        mode
                    }),
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

            const action = currentPumpOn ? 'off' : 'on';

            try {
                await requestJson('/dashboard/siram', {
                    method: 'POST',
                    body: JSON.stringify({
                        action
                    }),
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
        initReverbRealtime();
    </script>
</body>

</html>
