# Deploy & Test Firebase (Realtime DB + Functions)

Follow these exact commands on your development machine to deploy Cloud Functions and DB rules included in this repo.

Prerequisites
- Node.js (18+ recommended)
- npm
- Firebase CLI (`npm install -g firebase-tools`)
- A Firebase project (create at https://console.firebase.google.com)

1) Login and select project

```bash
npm install -g firebase-tools
firebase login
firebase projects:list
firebase use --add <PROJECT_ID>
```

2) Install Functions deps and deploy

```bash
cd functions
npm install
cd ..
firebase deploy --only functions,database
```

3) Verify

- Open Firebase Console → Realtime Database → check path `/smart_farming/telemetry/` and `/smart_farming/commands/`.
- See functions logs:

```bash
firebase functions:log
```

4) Test with curl (replace <PROJECT_ID> or use FIREBASE_BASE from `.env`)

```bash
curl -X POST "https://<PROJECT_ID>.firebaseio.com/smart_farming/telemetry/ESP32-01.json" \
  -d '{"soil_moisture":45.2,"air_temperature":27.5,"air_humidity":69.1}' \
  -H "Content-Type: application/json"
```

5) Deploy notes for ESP32

- Ensure `FIREBASE_BASE` in `esp32_smart_farming.ino` points to your Realtime DB URL (https://<PROJECT_ID>.firebaseio.com).
- Upload the sketch to ESP32 via Arduino IDE. Device will write telemetry and poll commands path.

6) Rollback

- To undo functions/db deploys, you can remove nodes or redeploy with empty rules.

Security
- The included `database.rules.json` is permissive for development. Configure rules to require authentication for production and limit device writes to their own telemetry path.
