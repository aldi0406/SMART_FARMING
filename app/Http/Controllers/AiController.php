<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;

class AiController extends Controller
{
    /**
     * Ambil data sensor terbaru dari Firebase Realtime Database.
     * Menggabungkan node telemetry, commands, dan legacy node.
     */
    private function fetchSensorData(): array
    {
        $firebaseBase = env('FIREBASE_BASE', 'https://smart-farming-90ebf-default-rtdb.firebaseio.com');
        $deviceUid    = env('SMART_FARMING_DEVICE_UID', 'ESP32-01');

        try {
            $responses = Http::pool(fn ($pool) => [
                $pool->get("{$firebaseBase}/smart_farming/telemetry/{$deviceUid}.json"),
                $pool->get("{$firebaseBase}/smart_farming/commands/{$deviceUid}.json"),
                $pool->get("{$firebaseBase}/smart_farming.json"),
            ]);

            $telemetry = $responses[0]->json() ?? [];
            $commands  = $responses[1]->json() ?? [];
            $legacy    = $responses[2]->json() ?? [];

            // Ambil entry terbaru jika telemetry adalah kumpulan log
            if (!empty($telemetry) && !array_key_exists('soil_moisture', $telemetry)) {
                $telemetry = end($telemetry) ?: [];
            }

            $soil  = $telemetry['soil_moisture']  ?? $legacy['soil_moisture']  ?? null;
            $temp  = $telemetry['air_temperature'] ?? $legacy['air_temperature'] ?? null;
            $hum   = $telemetry['air_humidity']    ?? $legacy['air_humidity']    ?? null;
            $pump  = $commands['pump_state']        ?? $legacy['pump_state']     ?? false;
            $mode  = $commands['pump_mode']         ?? $legacy['pump_mode']      ?? 'automatic';

            $soilStatus = $telemetry['soil_status']         ?? $legacy['soil_status']         ?? null;
            $tempStatus = $telemetry['temperature_status']  ?? $legacy['temperature_status']  ?? null;
            $humStatus  = $telemetry['humidity_status']     ?? $legacy['humidity_status']      ?? null;
            $recordedAt = $telemetry['recorded_at']         ?? $legacy['recorded_at']          ?? null;

            return compact('soil', 'temp', 'hum', 'pump', 'mode',
                           'soilStatus', 'tempStatus', 'humStatus', 'recordedAt');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Buat system prompt yang menyuntikkan data sensor real-time.
     */
    private function buildSystemPrompt(array $sensor): string
    {
        $now = now()->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm');

        // Cek dulu apakah $sensor kosong
        if (empty($sensor)) {
            $dataSection = "⚠️ Data sensor tidak tersedia saat ini (koneksi Firebase gagal).";
        } else {
            // Format nilai sensor dengan aman
            $soilStr  = ($sensor['soil'] ?? null) !== null  ? round($sensor['soil'])  . '%'     : 'tidak tersedia';
            $tempStr  = ($sensor['temp'] ?? null) !== null  ? $sensor['temp'] . '°C'            : 'tidak tersedia';
            $humStr   = ($sensor['hum']  ?? null) !== null  ? round($sensor['hum'])   . '%'     : 'tidak tersedia';
            $pumpStr  = ($sensor['pump'] ?? false) ? 'MENYALA (ON)'                   : 'MATI (OFF)';
            $modeStr  = ($sensor['mode'] ?? '') === 'manual' ? 'Manual'               : 'Otomatis';

            $soilLabel = match(strtolower($sensor['soilStatus'] ?? '')) {
                'low'     => '⚠️ KERING - perlu segera disiram',
                'optimum' => '✅ LEMBAB - kondisi ideal',
                'high'    => '💧 TERLALU BASAH - kurangi penyiraman',
                default   => '❓ status tidak diketahui',
            };
            $tempLabel = match(strtolower($sensor['tempStatus'] ?? '')) {
                'normal'  => '✅ NORMAL',
                'high'    => '🔥 TINGGI - waspadai tanaman',
                'low'     => '❄️ RENDAH',
                default   => '❓ tidak diketahui',
            };
            $humLabel = match(strtolower($sensor['humStatus'] ?? '')) {
                'normal', 'moderate' => '✅ NORMAL',
                'high'               => '💧 TINGGI',
                'low'                => '🌵 RENDAH',
                default              => '❓ tidak diketahui',
            };

            $dataSection = <<<DATA
📊 DATA SENSOR REAL-TIME (diperbarui: {$now}):
- Kelembapan Tanah : {$soilStr} → {$soilLabel}
- Suhu Udara       : {$tempStr} → {$tempLabel}
- Kelembapan Udara : {$humStr} → {$humLabel}
- Status Pompa     : {$pumpStr}
- Mode Pompa       : {$modeStr}
DATA;
        }

        return <<<PROMPT
Kamu adalah AGRI, asisten AI cerdas untuk sistem Smart Farming berbasis IoT.
Sistem ini memantau ladang menggunakan sensor ESP32 yang terhubung ke Firebase Realtime Database.
Saat ini kamu memiliki akses ke data sensor terbaru dari perangkat.

{$dataSection}

TUGAS DAN KEMAMPUAN KAMU:
1. Analisis kondisi tanaman berdasarkan data sensor di atas
2. Berikan rekomendasi penyiraman yang tepat (kapan, berapa lama)
3. Deteksi dan peringatkan kondisi abnormal (suhu ekstrem, kekeringan, dll)
4. Jawab pertanyaan tentang pertanian, budidaya tanaman, dan perawatan
5. Jelaskan arti dari setiap data sensor dengan bahasa sederhana
6. Berikan tips dan saran pertanian yang relevan

ATURAN MENJAWAB:
- Selalu gunakan data sensor di atas sebagai dasar utama analisis kamu
- Jawab dalam Bahasa Indonesia yang ramah, singkat, dan mudah dipahami
- Gunakan emoji secukupnya agar lebih menarik
- Jika data sensor menunjukkan kondisi kritis, prioritaskan peringatan tersebut
- Jangan memberikan jawaban yang terlalu panjang (maks 3-4 paragraf)

Nama kamu: AGRI (Agricultural Real-time Intelligence)
PROMPT;
    }

    /**
     * Fitur 1: AI Chatbot cerdas dengan konteks data sensor real-time.
     */
    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $sensor       = $this->fetchSensorData();
        $systemPrompt = $this->buildSystemPrompt($sensor);

        // Gunakan AnonymousAgent dari Laravel AI SDK dan arahkan ke provider groq dengan model Llama 3.3
        $agent = new \Laravel\Ai\AnonymousAgent($systemPrompt, [], []);
        
        try {
            $response = $agent->prompt($request->message, provider: 'groq', model: 'llama-3.3-70b-versatile');
            $replyText = $response->text;
        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Maaf, sistem AI sedang gangguan: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'reply'  => $replyText,
            'sensor' => $sensor,
        ]);
    }

    /**
     * Fitur 2: Voice Chatbot cerdas (Suara → Teks → AI dengan konteks → Suara).
     */
    public function voice(Request $request)
    {
        // Fitur Voice backend menggunakan STT dan TTS Gemini/OpenAI
        // Karena Gemini tidak memiliki STT/TTS bawaan di Laravel AI, fitur ini memerlukan 
        // penyesuaian khusus (misalnya pindah STT/TTS ke Frontend).
        return response()->json([
            'ai_reply' => 'Fitur suara sedang dalam perbaikan untuk menggunakan provider Gemini. Silakan gunakan chat teks untuk sementara waktu.',
            'user_text' => '(Voice Input)',
        ]);
    }
}
