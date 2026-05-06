# Langkah Menjalankan Project Sampai ESP32 dan Website Terhubung

Panduan ini step-by-step untuk menjalankan seluruh sistem sampai ESP32 bisa kirim data dan website menampilkan update real-time.

---

## STEP 1: Persiapan Awal (di folder project)

### 1.1 Cek dependency

Pastikan sudah install:
- PHP 8.3+
- Composer
- Node.js + npm
- MySQL running

### 1.2 Install package Laravel dan Node

```bash
composer install
npm install
```

### 1.3 Setup file .env

Di file `.env`, pastikan:

```ini
APP_URL=http://127.0.0.1:8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_farming
DB_USERNAME=root
DB_PASSWORD=
BROADCAST_CONNECTION=reverb
```

Sesuaikan `DB_PASSWORD` dengan password MySQL Anda.

### 1.4 Generate key Laravel

```bash
php artisan key:generate
```

### 1.5 Migrasi database

```bash
php artisan migrate
```

Expected: Muncul pesan "Migration table created successfully" dan beberapa tabel berhasil di-create.

---

## STEP 2: Jalankan Service Backend

**Buka 2-3 Terminal PowerShell terpisah untuk jalankan ini secara bersamaan.**

### Terminal 1: Web Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Expected:
```
Laravel development server started: http://0.0.0.0:8000
```

### Terminal 2: Reverb Websocket Server

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Expected:
```
Reverb starting...
Server running at 0.0.0.0:8080
```

### Terminal 3 (opsional): Vite Development Server

```bash
npm run dev
```

Expected:
```
  VITE v5.x.x  ready in xxx ms

  ➜  local:   http://127.0.0.1:5173/
```

---

## STEP 3: Buka Dashboard Website

---

## STEP 2.5: Setup Firebase (opsional — migrasi backend ke Realtime DB + Functions)

Jika Anda ingin menggantikan backend dengan Firebase Realtime Database + Cloud Functions, ikuti langkah berikut dari mesin pengembangan Anda.

1. Install Firebase CLI dan login:

```bash
npm install -g firebase-tools
firebase login
```

2. Pilih atau buat project Firebase di console.firebase.google.com dan catat `PROJECT_ID`.

3. Dari root project ini, inisialisasi (jika belum):

```bash
firebase use --add <PROJECT_ID>
```

4. Install dependency functions dan deploy (folder `functions` sudah disiapkan di repo):

```bash
cd functions
npm install
cd ..
firebase deploy --only functions,database
```

5. Verifikasi:

- Buka Firebase Console → Realtime Database untuk melihat data yang masuk di `/smart_farming/telemetry/{deviceUid}`.
- Lihat log Functions untuk `onTelemetryCreate` dan `onCommandWrite`:

```bash
firebase functions:log
```

6. Test manual menulis telemetry (contoh `curl`):

```bash
curl -X POST \
  "https://<PROJECT_ID>.firebaseio.com/smart_farming/telemetry/ESP32-01.json" \
  -d '{"soil_moisture":55.3,"air_temperature":28.4,"air_humidity":71.2}' \
  -H "Content-Type: application/json"
```

Fungsi `onTelemetryCreate` akan dijalankan otomatis dan menulis command/pump_state jika aturan terpenuhi.

Keamanan: file `database.rules.json` yang disertakan bersifat permissive untuk development. Untuk produksi, aktifkan authentication dan batasi penulisan agar device hanya menulis ke path telemetry miliknya.

Catatan ESP32: pastikan `FIREBASE_BASE` di `esp32_smart_farming.ino` mengarah ke URL Realtime DB (https://<PROJECT_ID>.firebaseio.com) dan unggah sketch yang sudah saya simpan di repo.


Di browser, buka:

```
http://127.0.0.1:8000
```

Expected:
- Muncul halaman dashboard dengan 4 kartu (soil moisture, temperature, humidity, pump status)
- Angka mungkin masih `N/A` atau 0 karena belum ada device dan data

Catat IP address laptop Anda di jaringan lokal (ketik `ipconfig` di PowerShell, cari IPv4 Address), misalnya `192.168.1.10`.

---

## STEP 4: Daftarkan Device ESP32

Buka PowerShell baru (Terminal 4):

```bash
php artisan tinker
```

Di prompt tinker, jalankan:

```php
App\Models\Device::create([
  'device_uid' => 'ESP32-01',
  'name' => 'ESP32 Greenhouse',
  'api_token' => 'token-demo-001',
  'is_active' => 1,
]);
```

Expected: Muncul output Device yang baru dibuat dengan `id: 1`.

Catat:
- `device_uid` = `ESP32-01`
- `api_token` = `token-demo-001`

Exit tinker dengan `exit`.

---

## STEP 5: Test API Manual (Sebelum ESP32)

Di PowerShell Terminal 4, test endpoint telemetry:

```powershell
$headers = @{
  "Content-Type" = "application/json"
  "X-Device-Token" = "token-demo-001"
}

$body = @{
  soil_moisture = 55.3
  air_temperature = 28.4
  air_humidity = 71.2
} | ConvertTo-Json

$response = Invoke-RestMethod `
  -Uri "http://127.0.0.1:8000/api/telemetry" `
  -Method POST `
  -Headers $headers `
  -Body $body

$response | ConvertTo-Json
```

Expected: Response berisi:
```json
{
  "message": "Telemetry berhasil disimpan.",
  "data": {
    "reading_id": 1,
    "device_id": "ESP32-01",
    "soil_status": "moderate",
    "temperature_status": "normal",
    "humidity_status": "normal",
    "pump_mode": "automatic",
    "pump_state": false,
    "pump_action": "no_change",
    "cooldown_wait_seconds": 0,
    "recorded_at": "2026-05-01T..."
  }
}
```

Jika berhasil, angka di dashboard website harusnya berubah.

**Catat Response ini, terutama nilai `data.pump_state` dan field lainnya. Ini format yang nantinya ESP32 akan terima.**

---

## STEP 6: Siapkan Firmware ESP32

Buka Arduino IDE di laptop Anda.

### 6.1 Install Library

Di Arduino IDE, buka `Tools > Manage Libraries`:

Cari dan install:
- `ArduinoJson` (oleh Benoit Blanchon)
- `DHT sensor library` (oleh Adafruit) - jika Anda pakai DHT11/DHT22

### 6.2 Buat Sketch Baru

Copy-paste sketch berikut dan **sesuaikan**:

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ===== KONFIGURASI WIFI & SERVER =====
const char* WIFI_SSID = "NAMA_WIFI_ANDA";
const char* WIFI_PASS = "PASSWORD_WIFI";

// PENTING: Gunakan IP laptop dari `ipconfig`, bukan localhost
const char* SERVER_IP = "192.168.1.10";
const int SERVER_PORT = 8000;

const char* DEVICE_UID = "ESP32-01";
const char* DEVICE_TOKEN = "token-demo-001";

// ===== PIN HARDWARE =====
const int RELAY_PIN = 26;          // Pin relay pompa
const int SOIL_SENSOR_PIN = 34;    // ADC pin soil moisture
const int DHT_PIN = 4;             // Pin DHT11/DHT22 (opsional)

// ===== TIMING =====
unsigned long lastSendTime = 0;
const unsigned long SEND_INTERVAL = 2000; // 2 detik

bool pumpOn = false;

// ===== FUNGSI BACA SENSOR =====

float readSoilMoisture() {
  // Baca ADC dan konversi ke persen
  int rawValue = analogRead(SOIL_SENSOR_PIN);
  
  // Sesuaikan dengan kalibrasi sensor Anda:
  // rawValue 0 (kering) -> 0%
  // rawValue 4095 (basah) -> 100%
  // Contoh simple:
  float percent = (rawValue / 4095.0) * 100.0;
  
  return constrain(percent, 0, 100);
}

float readTemperature() {
  // GANTI dengan pembacaan DHT11 asli
  // Contoh placeholder: 28.0 derajat
  return 28.0;
}

float readHumidity() {
  // GANTI dengan pembacaan DHT11 asli
  // Contoh placeholder: 70.0%
  return 70.0;
}

// ===== FUNGSI WIFI =====

void connectWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(WIFI_SSID);
  
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 40) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected!");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi connect failed!");
  }
}

// ===== FUNGSI SEND TELEMETRY =====

void sendTelemetry() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected, reconnecting...");
    connectWiFi();
    return;
  }
  
  float soil = readSoilMoisture();
  float temp = readTemperature();
  float hum = readHumidity();
  
  Serial.print("Sensor: soil=");
  Serial.print(soil);
  Serial.print("% temp=");
  Serial.print(temp);
  Serial.print("C hum=");
  Serial.print(hum);
  Serial.println("%");
  
  // Build URL
  String url = "http://";
  url += SERVER_IP;
  url += ":";
  url += SERVER_PORT;
  url += "/api/telemetry";
  
  // Build JSON payload
  StaticJsonDocument<256> payload;
  payload["soil_moisture"] = soil;
  payload["air_temperature"] = temp;
  payload["air_humidity"] = hum;
  
  String jsonBody;
  serializeJson(payload, jsonBody);
  
  // HTTP POST
  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);
  
  int httpCode = http.POST(jsonBody);
  String response = http.getString();
  
  Serial.print("HTTP Code: ");
  Serial.println(httpCode);
  Serial.print("Response: ");
  Serial.println(response);
  
  // Parse response dan ambil pump_state
  if (httpCode == 200 || httpCode == 201) {
    StaticJsonDocument<1024> doc;
    DeserializationError err = deserializeJson(doc, response);
    
    if (!err) {
      bool pumpStateFromServer = doc["data"]["pump_state"] | false;
      String pumpMode = doc["data"]["pump_mode"] | "unknown";
      
      Serial.print("Pump Mode: ");
      Serial.println(pumpMode);
      Serial.print("Pump State: ");
      Serial.println(pumpStateFromServer ? "ON" : "OFF");
      
      // Set relay sesuai state dari server
      pumpOn = pumpStateFromServer;
      digitalWrite(RELAY_PIN, pumpOn ? HIGH : LOW);
      
    } else {
      Serial.print("JSON parse error: ");
      Serial.println(err.c_str());
    }
  } else {
    Serial.println("Telemetry send failed!");
  }
  
  http.end();
  lastSendTime = millis();
}

// ===== SETUP =====

void setup() {
  Serial.begin(115200);
  delay(2000);
  
  Serial.println("\n\nESP32 Smart Farming Device Started");
  Serial.println("==================================");
  
  // Setup GPIO
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW); // Pompa off saat startup
  
  // Connect WiFi
  connectWiFi();
  
  Serial.println("Ready to send telemetry...");
}

// ===== LOOP =====

void loop() {
  unsigned long currentTime = millis();
  
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected, reconnecting...");
    connectWiFi();
    delay(5000);
    return;
  }
  
  // Send telemetry setiap SEND_INTERVAL ms
  if (currentTime - lastSendTime >= SEND_INTERVAL) {
    sendTelemetry();
  }
  
  delay(100);
}
```

### 6.3 Sesuaikan Konfigurasi

Di bagian `===== KONFIGURASI WIFI & SERVER =====`, ganti:

- `WIFI_SSID` → nama WiFi Anda
- `WIFI_PASS` → password WiFi
- `SERVER_IP` → IP laptop Anda dari `ipconfig` (misalnya `192.168.1.10`)
- `DEVICE_UID` → `ESP32-01` (harus sama dengan yang di database)
- `DEVICE_TOKEN` → `token-demo-001` (harus sama dengan yang di database)

### 6.4 Sesuaikan Pin Hardware

Di bagian `===== PIN HARDWARE =====`, pastikan:

- `RELAY_PIN = 26` (atau pin yang terhubung ke relay pompa)
- `SOIL_SENSOR_PIN = 34` (atau pin ADC soil moisture sensor)
- `DHT_PIN = 4` (atau pin yang terhubung DHT11)

### 6.5 Sesuaikan Sensor (Opsional tapi Penting)

Fungsi `readSoilMoisture()`, `readTemperature()`, `readHumidity()` harus sesuai hardware Anda:

**Untuk DHT11** (beli library Adafruit DHT):

```cpp
#include <DHT.h>

DHT dht(DHT_PIN, DHT11);

void setup() {
  // ... kode lainnya
  dht.begin();
}

float readTemperature() {
  return dht.readTemperature();
}

float readHumidity() {
  return dht.readHumidity();
}
```

**Untuk Soil Moisture Sensor Analog:**

```cpp
float readSoilMoisture() {
  int rawValue = analogRead(SOIL_SENSOR_PIN);
  
  // Kalibrasi:
  // - Sensor di udara (kering): raw ~= 4095
  // - Sensor di air (basah): raw ~= 1000
  
  int dryValue = 4095;  // Ganti dengan value kering sensor Anda
  int wetValue = 1000;  // Ganti dengan value basah sensor Anda
  
  float percent = 100.0 - ((rawValue - wetValue) / (float)(dryValue - wetValue)) * 100.0;
  return constrain(percent, 0, 100);
}
```

---

## STEP 7: Upload Firmware ke ESP32

### 7.1 Pilih Board di Arduino IDE

- `Tools > Board > ESP32 > ESP32 Dev Module` (atau sesuai board Anda)
- `Tools > Port > COM...` (pilih COM port ESP32)

### 7.2 Upload

Klik tombol **Upload** (tombol panah kanan di toolbar).

Expected: Sketch berhasil upload. Di Serial Monitor muncul:

```
ESP32 Smart Farming Device Started
==================================
Connecting to WiFi: NAMA_WIFI_ANDA
...
WiFi connected!
IP: 192.168.x.x
Ready to send telemetry...
```

---

## STEP 8: Monitor Serial dan Verifikasi Koneksi

Di Arduino IDE, buka `Tools > Serial Monitor` (baudrate `115200`).

Harusnya muncul setiap 2 detik:

```
Sensor: soil=55.30% temp=28.00C hum=70.00%
HTTP Code: 201
Response: {"message":"Telemetry berhasil disimpan.","data":{...}}
Pump Mode: automatic
Pump State: OFF
```

Jika muncul:
- `HTTP Code: 401` → Token salah atau device tidak aktif
- `HTTP Code: 422` → Format JSON salah atau range angka tidak valid
- `HTTP Code: 0` atau timeout → IP server salah atau server tidak running

---

## STEP 9: Lihat Update Real-time di Dashboard

Di browser, buka dashboard:

```
http://127.0.0.1:8000
```

Dalam 2 detik, kartu akan update dengan nilai sensor dari ESP32:

- **Soil Moisture**: 55.3%
- **Air Temperature**: 28.0°C
- **Air Humidity**: 70.0%
- **Pump Status**: OFF

Dashboard mungkin menampilkan nilai dengan jeda (karena websocket fallback ke polling).

---

## STEP 10: Test Interaksi (Manual Override)

Di dashboard, ada tombol:

- **Mode Manual** / **Mode Otomatis** → klik untuk ubah mode
- **SIRAM** → tombol untuk nyalakan/matikan pompa manual (hanya aktif saat Mode Manual)

Saat Anda klik **SIRAM** di website:

1. Backend update tabel `pump_states`
2. Broadcast event ke websocket channel
3. Saat ESP32 send telemetry berikutnya, respons akan berisi state pompa terbaru
4. ESP32 set relay sesuai state
5. Relay fisik nyala/mati (motor pompa hidup/mati)

---

## STEP 11: Cek Database

Terminal baru:

```bash
php artisan tinker
```

Check data:

```php
// Lihat device
App\Models\Device::all();

// Lihat readings terbaru
App\Models\Reading::latest()->limit(5)->get();

// Lihat pump state
App\Models\PumpState::all();

// Lihat pump logs
App\Models\PumpLog::latest()->limit(5)->get();
```

Expected: Tabel `readings` bertambah setiap 2 detik (sesuai interval ESP32).

---

## Checklist Sukses

- ✅ `php artisan migrate` berhasil
- ✅ `php artisan serve` berjalan
- ✅ `php artisan reverb:start` berjalan
- ✅ Dashboard web bisa dibuka (http://127.0.0.1:8000)
- ✅ Device ESP32 terdaftar di database
- ✅ API test manual berhasil (HTTP 201)
- ✅ ESP32 upload sketch dan serial monitor muncul log
- ✅ HTTP Code 201 di serial ESP32
- ✅ Dashboard update dengan nilai sensor
- ✅ Tombol Mode dan SIRAM responsif
- ✅ Database `readings` bertambah
- ✅ Relay pompa berubah saat manual override

---

## Troubleshooting

### ESP32 tidak connect WiFi

- Cek SSID dan password di sketch
- Pastikan ESP32 sudah support 2.4GHz WiFi (jika router Anda 5GHz only)
- Cek serial output: `WiFi connect failed!`

### HTTP Code 0 (timeout)

- `SERVER_IP` salah → gunakan `ipconfig` di Windows untuk cek IP LAN
- Port `8000` belum dibuka firewall Windows
- Coba ping dari ESP32: ubah HTTP POST jadi PING untuk test koneksi

### HTTP Code 401

- Header `X-Device-Token` tidak sesuai
- Device `is_active` = false di database
- Run `App\Models\Device::find(1)->update(['is_active' => true]);`

### HTTP Code 422

- Field JSON tidak lengkap (harus ada `soil_moisture`, `air_temperature`, `air_humidity`)
- Nilai di luar range:
  - soil_moisture: 0-100
  - air_temperature: -20 sampai 80
  - air_humidity: 0-100

### Dashboard tidak update

- Pastikan `/api/telemetry` OK dulu (test manual dulu)
- Refresh browser (Ctrl+F5)
- Buka Developer Console (F12) lihat ada error?
- Cek Reverb server sudah jalan

### Websocket tidak connect

- Pastikan `php artisan reverb:start` jalan di terminal
- Buka firewall port `8080`
- Di browser console cek: `Connection failed` atau `CONNECTING`
- Tapi polling fallback tetap jalan, jadi dashboard tetap update (cuma sedikit lambat)

---

## Selesai! 🎉

Project sudah connected:
- Website bisa lihat data sensor real-time dari ESP32
- Website bisa kontrol pompa manual
- ESP32 bisa baca status pompa dari backend dan menggerakkan relay

Selanjutnya bisa develop:
- Tambah sensor lain
- Auto rule yang lebih kompleks
- Mobile app
- Data logging ke cloud

