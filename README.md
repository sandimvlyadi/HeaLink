# HeaLink

Platform kesehatan mental berbasis AI untuk monitoring pasien, analitik risiko, telekonsultasi, dan notifikasi real-time.

## Overview

HeaLink dibangun dengan stack Laravel + Inertia + React untuk web dashboard, serta REST API untuk kebutuhan mobile app.

### Tech Stack

- Backend: Laravel 13, PHP 8.5
- Frontend: React 19, Inertia.js v3, Tailwind CSS v4, shadcn/ui
- Database: PostgreSQL (production), SQLite (default local)
- Queue & Cache: Redis
- Real-time: Laravel Reverb + Echo
- API Docs: Scramble (OpenAPI + Swagger UI)

## Fitur Utama

Berikut ringkasan fitur berdasarkan route web dan API yang terdaftar.

### Web Dashboard (Inertia)

- Dashboard utama (`/dashboard`)
- Manajemen pasien (`/patients`, detail pasien, chat log)
- Manajemen konsultasi (list, room, start/cancel/complete)
- Notifikasi (list, mark read, mark all read)
- Risk dashboard (`/risk`)
- Laporan (`/reports`)
- Admin panel (`/admin/users`, `/admin/statistics`)
- Patient portal untuk konsultasi pribadi (`/my/consultations`)
- Settings: profile, security, appearance, teams

### REST API (`/api/v1`)

- Auth: register, login, logout, me, update profile
- Vitals: sync wearable data, latest, history
- Sleep logs: store, history
- Screening: upsert, latest
- Chat: kirim pesan, riwayat chat
- Mood journal: create, list/filter
- Mental status: list, latest
- Consultations: list, create, show, cancel, start, complete
- Notifications: list, read, read all

## Local Development

## Prasyarat

- PHP 8.5+
- Composer 2+
- Node.js 24+ dan npm
- Redis 7+
- PostgreSQL 16+ (recommended) atau SQLite untuk quick local setup

## Setup Cepat

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
```

### Generate Reverb

```bash
php artisan tinker
echo 'REVERB_APP_ID=' . random_int(100000, 999999) . PHP_EOL;
echo 'REVERB_APP_KEY=' . \Illuminate\Support\Str::random(20) . PHP_EOL;
echo 'REVERB_APP_SECRET=' . \Illuminate\Support\Str::random(32) . PHP_EOL;
```

## Menjalankan Aplikasi (Mode Dev)

Gunakan satu command berikut untuk menjalankan web server, queue listener, log tailing, dan Vite sekaligus:

```bash
composer run dev
```

Aplikasi web default berjalan di:

- http://localhost:8000

Jika Anda menggunakan fitur real-time (Reverb), jalankan juga:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

## Quality Checks

```bash
php artisan test --compact
npm run lint:check
npm run types:check
npm run format:check
```

## Deployment

## Opsi A: Docker Compose Production (Direkomendasikan)

Project sudah menyediakan `docker-compose.prod.yml` dengan service:

- app (FrankenPHP) di port `8086`
- reverb di port `8087`
- queue worker
- scheduler
- redis
- postgres

### Langkah Deploy

```bash
cp .env.prod .env
docker compose -f docker-compose.prod.yml build app --no-cache
docker compose -f docker-compose.prod.yml up -d --build
```

Lalu jalankan migrasi:

```bash
docker exec -it healink-app php artisan migrate --force
docker exec -it healink-app php artisan db:seed --class=RiskThresholdSeeder --force
docker exec -it healink-app php artisan db:seed --class=UserSeeder --force
```

Akses aplikasi:

- App: http://YOUR_SERVER:8086
- Reverb: http://YOUR_SERVER:8087

## Opsi B: Deploy Tanpa Docker

Contoh flow umum di server Linux:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Pastikan service berikut dikelola process manager (Supervisor/Systemd):

- `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
- `php artisan reverb:start --host=0.0.0.0 --port=8080`
- scheduler per menit (`php artisan schedule:run`)

## Swagger UI / OpenAPI Documentation

Dokumentasi API tersedia via Scramble:

- Swagger UI: `/docs/api`
- OpenAPI JSON: `/api.json`

Contoh lokal:

- http://localhost:8000/docs/api
- http://localhost:8000/api.json

Catatan:

- API path yang didokumentasikan adalah `/api/v1`
- Beberapa endpoint memerlukan Bearer token (Sanctum)

## Struktur Singkat

- `routes/web.php` untuk web dashboard
- `routes/api.php` untuk REST API mobile
- `config/scramble.php` untuk konfigurasi API docs

## Kontribusi

Jika ingin berkontribusi:

1. Buat branch baru
2. Jalankan test/lint sebelum pull request
3. Pastikan perubahan tidak merusak route dan kontrak API

## Author

- Nama: Sandi Mulyadi
- Email: sandimvlyadi@gmail.com
