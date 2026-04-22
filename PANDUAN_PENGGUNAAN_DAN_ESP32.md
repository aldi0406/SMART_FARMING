# Panduan Penggunaan Project dan Koneksi ESP32

Dokumen ini menjelaskan cara menjalankan project Smart Farming dan menghubungkannya dengan ESP32.

## 1. Prasyarat

Pastikan tools berikut sudah terpasang:

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL/MariaDB
- Board ESP32 di Arduino IDE
- Library Arduino:
  - WiFi (bawaan)
  - HTTPClient (bawaan)
  - ArduinoJson
  - DHT sensor library (jika pakai DHT11)

## 2. Setup Project Laravel

Dari folder project:

```bash
composer install
npm install
```

Konfigurasi environment di file `.env`:

- `APP_URL=http://127.0.0.1:8000` (untuk akses lokal)
- `DB_*` sesuai database Anda
- `BROADCAST_CONNECTION=reverb`

Generate key (jika belum):

```bash
php artisan key:generate
```

Migrasi database:

```bash
php artisan migrate
```

## 3. Menjalankan Service

### Opsi A (satu perintah)

```bash
composer run dev
```

### Opsi B (manual, terminal terpisah)

Terminal 1:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Terminal 2:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Terminal 3 (opsional, frontend assets):

```bash
npm run dev
```

## 4. Daftarkan Device ESP32

Buat 1 device untuk autentikasi token API:

```bash
php artisan tinker --execute "App\\Models\\Device::updateOrCreate(['device_uid' => 'ESP32-01'], ['name' => 'ESP32 Greenhouse', 'api_token' => 'TOKEN-RAHASIA-ANDA', 'is_active' => true]);"
```

Catat nilai:

- `device_uid` contoh: `ESP32-01`
- `api_token` contoh: `TOKEN-RAHASIA-ANDA`

Token ini wajib dikirim dari ESP32 melalui header `X-Device-Token`.

## 5. Endpoint yang Dipakai ESP32

Endpoint utama:

- `POST /api/telemetry`

Header wajib:

- `Content-Type: application/json`
- `X-Device-Token: TOKEN-RAHASIA-ANDA`

Body JSON contoh:

```json
{
  "soil_moisture": 55.3,
  "air_temperature": 28.4,
  "air_humidity": 71.2,
  "pump_state": false,
  "recorded_at": "2026-04-22T10:20:00+07:00"
}
```

## 6. Alur Kontrol Pompa yang Disarankan

Agar sinkron antara dashboard web dan ESP32, gunakan alur ini:

1. ESP32 baca sensor.
2. ESP32 kirim data ke `POST /api/telemetry`.
3. Backend hitung rule mode automatic/manual.
4. Backend kirim respons JSON yang berisi state pompa terbaru.
5. ESP32 baca nilai `data.pump_state` dari respons.
6. ESP32 set relay sesuai nilai tersebut.

Dengan alur ini, pompa tetap bisa dikontrol dari dashboard dan perangkat selalu ikut status terbaru dari backend.

## 7. Contoh Sketch ESP32 (Minimal)

> Sesuaikan pin, jenis sensor, dan rumus kalibrasi soil moisture dengan hardware Anda.

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* WIFI_SSID = "NAMA_WIFI";
const char* WIFI_PASS = "PASSWORD_WIFI";

// Penting: gunakan IP laptop/server di jaringan yang sama, JANGAN localhost
const char* API_URL = "http://192.168.1.10:8000/api/telemetry";
const char* DEVICE_TOKEN = "TOKEN-RAHASIA-ANDA";

const int RELAY_PIN = 26;

float readSoilMoisturePercent() {
  // Ganti dengan pembacaan sensor asli + kalibrasi
  int raw = analogRead(34);
  float pct = 100.0f - ((raw / 4095.0f) * 100.0f);
  if (pct < 0) pct = 0;
  if (pct > 100) pct = 100;
  return pct;
}

float readAirTemperature() {
  // Ganti dengan pembacaan DHT11
  return 28.0f;
}

float readAirHumidity() {
  // Ganti dengan pembacaan DHT11
  return 70.0f;
}

void connectWiFi() {
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  connectWiFi();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  float soil = readSoilMoisturePercent();
  float temp = readAirTemperature();
  float hum = readAirHumidity();

  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);

  StaticJsonDocument<256> payload;
  payload["soil_moisture"] = soil;
  payload["air_temperature"] = temp;
  payload["air_humidity"] = hum;

  String body;
  serializeJson(payload, body);

  int code = http.POST(body);
  String resp = http.getString();

  Serial.print("HTTP Code: ");
  Serial.println(code);
  Serial.println(resp);

  if (code == 200 || code == 201) {
    StaticJsonDocument<1024> doc;
    DeserializationError err = deserializeJson(doc, resp);
    if (!err) {
      bool pumpState = doc["data"]["pump_state"] | false;

      // Sesuaikan HIGH/LOW dengan tipe relay Anda (active high / active low)
      digitalWrite(RELAY_PIN, pumpState ? HIGH : LOW);
    }
  }

  http.end();
  delay(1000);
}
```

## 8. Cara Akses Dashboard

Buka browser:

- `http://127.0.0.1:8000` (di mesin yang sama)
- atau `http://IP-LAPTOP:8000` (dari perangkat lain 1 jaringan)

Dashboard akan update real-time via Reverb. Jika websocket putus, sistem fallback ke polling.

## 9. Verifikasi Cepat

Checklist sukses:

- `php artisan migrate` berhasil
- Request ESP32 ke `/api/telemetry` mendapat HTTP 201
- Data masuk ke tabel `readings`
- Tabel `pump_states` berubah saat mode/siram diubah
- Dashboard menampilkan angka terbaru tanpa refresh manual

## 10. Troubleshooting

### ESP32 tidak bisa akses API

- Pastikan pakai IP LAN laptop/server, bukan `localhost`
- Pastikan ESP32 dan server di WiFi yang sama
- Buka firewall Windows untuk port `8000` dan `8080`

### Respons 401 Unauthorized

- Header `X-Device-Token` salah
- Device `is_active = false`

### Respons 422 Unprocessable Entity

- Field JSON tidak lengkap
- Nilai di luar range valid:
  - `soil_moisture` 0-100
  - `air_humidity` 0-100
  - `air_temperature` -20 sampai 80

### Dashboard tidak real-time

- Pastikan `php artisan reverb:start` berjalan
- Pastikan nilai `REVERB_*` dan `VITE_REVERB_*` di `.env` benar
- Hard refresh browser lalu cek console log

---

Jika diperlukan, Anda bisa menambahkan endpoint khusus command pull untuk ESP32 (misalnya `GET /api/device/command`) agar perangkat bisa mengambil instruksi selain dari respons telemetry.
