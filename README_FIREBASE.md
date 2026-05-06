# Firebase migration notes

This project now includes a Firebase-based backend (Realtime Database + Cloud Functions) to replace the Laravel-only backend for telemetry ingestion and pump control.

Quick steps:

1. Install Firebase CLI and login:

```bash
npm install -g firebase-tools
firebase login
```

2. From project root, deploy functions and database rules:

```bash
cd functions
npm install
cd ..
firebase deploy --only functions,database
```

3. Configure database rules in `database.rules.json`. The provided rules are permissive for local testing — lock them down for production.

4. ESP32 sketch now posts telemetry to the Realtime DB path `/smart_farming/telemetry/{deviceUid}` and polls `/smart_farming/commands/{deviceUid}` for commands. Use the `FIREBASE_BASE` constant in `esp32_smart_farming.ino`.

Security notes:
- For production, enforce authentication on rules and/or use short-lived custom tokens for devices.
- Rotate service account keys and protect them.
