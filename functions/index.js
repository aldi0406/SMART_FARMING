const functions = require("firebase-functions");
const admin = require("firebase-admin");

admin.initializeApp();

// Configuration (tweak thresholds here or use environment config)
const SOIL_ON_BELOW = 40.0;
const SOIL_OFF_ABOVE = 70.0;
const COOLDOWN_SECONDS = 60; // seconds

exports.onTelemetryCreate = functions.database
    .ref("/smart_farming/telemetry/{deviceUid}/{pushId}")
    .onCreate(async (snapshot, context) => {
        const data = snapshot.val();
        const deviceUid = context.params.deviceUid;
        const now = new Date().toISOString();

        try {
            // Ensure telemetry node exists (we already have data)
            const soil = parseFloat(data.soil_moisture || 0);
            const temp = parseFloat(data.air_temperature || 0);
            const hum = parseFloat(data.air_humidity || 0);

            // Read current pump state
            const pumpStateRef = admin.database().ref(`/smart_farming/pump_state/${deviceUid}`);
            const pumpStateSnap = await pumpStateRef.get();
            const currentState = pumpStateSnap.exists() ? pumpStateSnap.val() : { is_on: false, last_changed_at: null, mode: 'automatic' };

            // Check cooldown
            let allowed = true;
            if (currentState.last_changed_at) {
                const last = new Date(currentState.last_changed_at);
                const elapsed = (Date.now() - last.getTime()) / 1000;
                if (elapsed < COOLDOWN_SECONDS) allowed = false;
            }

            // Determine desired state by automatic rule
            let desiredState = null;
            let reason = null;
            if (!currentState.is_on && soil < SOIL_ON_BELOW) {
                desiredState = true;
                reason = 'soil_moisture_below_threshold';
            }
            if (currentState.is_on && soil > SOIL_OFF_ABOVE) {
                desiredState = false;
                reason = 'soil_moisture_above_threshold';
            }

            // Log reading summary under readings (optional)
            const readingLogRef = admin.database().ref(`/smart_farming/readings/${deviceUid}`);
            await readingLogRef.push({ soil_moisture: soil, air_temperature: temp, air_humidity: hum, recorded_at: now, telemetry_ref: snapshot.ref.toString() });

            if (desiredState === null) {
                // No change needed
                return null;
            }

            if (!allowed) {
                // Cooldown blocked — write a log entry and return
                const waitSeconds = COOLDOWN_SECONDS - ((Date.now() - new Date(currentState.last_changed_at).getTime()) / 1000);
                await admin.database().ref(`/smart_farming/pump_logs/${deviceUid}`).push({ source: 'automatic_rule', action: 'cooldown_blocked', reason: reason, wait_seconds: Math.max(0, Math.round(waitSeconds)), at: now });
                return null;
            }

            if ((!!currentState.is_on) === (!!desiredState)) {
                // state already matches
                return null;
            }

            // Apply state change
            await pumpStateRef.set({ is_on: desiredState, mode: 'automatic', last_changed_at: now, last_reason: reason });

            // Write command for device to read
            const commandRef = admin.database().ref(`/smart_farming/commands/${deviceUid}`);
            await commandRef.set({ pump_mode: 'automatic', pump_state: desiredState, reason: reason, last_changed_at: now });

            // Log action
            await admin.database().ref(`/smart_farming/pump_logs/${deviceUid}`).push({ source: 'automatic_rule', action: desiredState ? 'switched_on' : 'switched_off', from_state: currentState.is_on, to_state: desiredState, reason: reason, at: now });

            return null;
        } catch (err) {
            console.error('onTelemetryCreate error:', err);
            return null;
        }
    });

// Respond to explicit commands written to /smart_farming/commands/{deviceUid}
// Ensures pump_state node and logs are updated consistently.
exports.onCommandWrite = functions.database
    .ref('/smart_farming/commands/{deviceUid}')
    .onWrite(async (change, context) => {
        const deviceUid = context.params.deviceUid;
        const after = change.after.val();
        if (!after) return null; // deleted

        try {
            const pumpState = !!after.pump_state;
            const pumpMode = after.pump_mode || 'manual';
            const reason = after.reason || 'command_from_client';
            const now = new Date().toISOString();

            // Update canonical pump_state node
            const pumpStateRef = admin.database().ref(`/smart_farming/pump_state/${deviceUid}`);
            await pumpStateRef.set({ is_on: pumpState, mode: pumpMode, last_changed_at: now, last_reason: reason });

            // Append to pump logs
            const logRef = admin.database().ref(`/smart_farming/pump_logs/${deviceUid}`);
            await logRef.push({ source: 'command', action: pumpState ? 'switched_on' : 'switched_off', reason, at: now });

            console.log(`Processed command for ${deviceUid}: pump_state=${pumpState}`);
            return null;
        } catch (err) {
            console.error('onCommandWrite error:', err);
            return null;
        }
    });
