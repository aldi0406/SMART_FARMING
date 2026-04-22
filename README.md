<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Smart Farming API (Fase 1)

Endpoint untuk menerima data sensor perangkat:

- `POST /api/telemetry`
- Header wajib: `X-Device-Token`

### Device Contoh untuk Testing

- `device_uid`: `ESP32-001`
- `token`: `dev-token-demo-001`

### Contoh Request

```bash
curl -X POST http://127.0.0.1:8000/api/telemetry \
	-H "Content-Type: application/json" \
	-H "X-Device-Token: dev-token-demo-001" \
	-d '{
		"soil_moisture": 75.2,
		"air_temperature": 24.8,
		"air_humidity": 62.1,
		"pump_state": true,
		"recorded_at": "2026-04-11T12:30:00+07:00"
	}'
```

### Catatan Status Turunan

- `soil_status`:
	- `< 40` => `low`
	- `40 - 70` => `moderate`
	- `> 70` => `optimum`
- `temperature_status`:
	- `< 20` => `low`
	- `20 - 30` => `normal`
	- `> 30` => `high`
- `humidity_status`:
	- `< 40` => `low`
	- `40 - 70` => `normal`
	- `> 70` => `high`

## Smart Farming API (Fase 2)

Fase 2 menambahkan mode pompa manual/otomatis, rule threshold, cooldown, dan log histori.

### Endpoint Status Pompa

- `GET /api/pump/state`
- Header wajib: `X-Device-Token`

```bash
curl -X GET http://127.0.0.1:8000/api/pump/state \
	-H "X-Device-Token: dev-token-demo-001"
```

### Endpoint Kontrol Pompa

- `POST /api/pump/command`
- Header wajib: `X-Device-Token`

#### Set mode manual + nyalakan pompa

```bash
curl -X POST http://127.0.0.1:8000/api/pump/command \
	-H "Content-Type: application/json" \
	-H "X-Device-Token: dev-token-demo-001" \
	-d '{
		"mode": "manual",
		"pump_state": true,
		"reason": "manual_button"
	}'
```

#### Balik ke mode otomatis

```bash
curl -X POST http://127.0.0.1:8000/api/pump/command \
	-H "Content-Type: application/json" \
	-H "X-Device-Token: dev-token-demo-001" \
	-d '{
		"mode": "automatic"
	}'
```

### Rule Otomatis dan Cooldown

- ON jika `soil_moisture < 40`
- OFF jika `soil_moisture > 70`
- Cooldown default `60` detik antar perubahan state pompa
- Pengaturan berada di `config/smart_farming.php`

## Smart Farming Dashboard Realtime (Fase 3)

Dashboard web sekarang update otomatis tanpa refresh halaman.

### Endpoint Dashboard (Web)

- `GET /dashboard/live` => data sensor + state pompa terbaru
- `POST /dashboard/mode` => ubah mode pompa (`manual` / `automatic`)
- `POST /dashboard/siram` => tombol siram manual (`on` / `off` / `toggle`)

### Cara Kerja Realtime

- Dashboard menggunakan WebSocket Laravel Reverb (channel `smart-farming.dashboard`)
- Polling ke `GET /dashboard/live` hanya dipakai sebagai fallback jika koneksi WebSocket terputus
- Interval fallback bisa diatur di `config/smart_farming.php` pada key `dashboard.refresh_interval_seconds`
- Tombol `SIRAM` hanya aktif saat mode `manual`

### Menjalankan Reverb Lokal

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Atau jalankan semua service sekaligus:

```bash
composer run dev
```

### Catatan Integrasi Device

- Device tetap kirim telemetry ke `POST /api/telemetry`
- Dashboard menampilkan data terbaru dari tabel readings dan pump_states
