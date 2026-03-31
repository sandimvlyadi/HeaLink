# 🧠 HeaLink Platform — AI Agent Prompt & Engineering Guideline

### Laravel 13 + React + shadcn/ui — Full-Stack Web & Backend

> Dokumen ini adalah **master prompt** untuk AI agent di code editor (Cursor, Windsurf, Copilot, dll).
> Simpan sebagai `.cursorrules` (Cursor) atau `AGENTS.md` (Windsurf) di root project.
> **Baca dokumen ini sepenuhnya sebelum menulis satu baris kode pun.**

---

## 📋 PROJECT OVERVIEW

| Key                | Value                                                            |
| ------------------ | ---------------------------------------------------------------- |
| **Nama Aplikasi**  | HeaLink                                                          |
| **Stack Backend**  | Laravel 13 (PHP 8.4+)                                            |
| **Stack Frontend** | React 19 + Inertia.js v2 + shadcn/ui + Tailwind CSS v4           |
| **Database**       | PostgreSQL 16+                                                   |
| **Cache / Queue**  | Redis 7+                                                         |
| **Real-time**      | Laravel Reverb (WebSocket native)                                |
| **Auth**           | Laravel Sanctum — API token (mobile) + session (web via Inertia) |
| **Storage**        | Laravel Storage (S3-compatible / local disk)                     |
| **Build Tool**     | Vite 6                                                           |
| **Testing**        | Pest PHP v3                                                      |

**Peran Sistem:** Web Dashboard untuk Unit Kesehatan / Dokter / Admin — dikonsumsi juga sebagai REST API oleh Mobile App (Flutter).

**Arsitektur:** Laravel sebagai API-first backend + Inertia.js sebagai jembatan ke React frontend (SPA feel, SSR-ready, tanpa perlu REST API terpisah untuk web). Mobile Flutter tetap konsumsi REST API JSON via `/api/*`.

---

## 🎯 AKTOR & OTORISASI

| Role      | Akses                                                 | Platform              |
| --------- | ----------------------------------------------------- | --------------------- |
| `patient` | Chatbot, journal, data diri, notifikasi               | Mobile App (REST API) |
| `medic`   | Dashboard pasien, telemedicine, intervensi, chat log  | Web Dashboard         |
| `admin`   | User management, statistik global, konfigurasi risiko | Web Dashboard         |

---

## 🗄️ STRATEGI DATABASE — POSTGRESQL

### Prinsip Utama

1. **Primary Key:** Gunakan `bigIncrements` (`id` BIGINT) untuk semua tabel — dipakai untuk relasi internal (FK) karena performa JOIN lebih baik dari UUID.
2. **Public ID:** Setiap tabel memiliki kolom `uuid` (UUID v4) yang di-expose ke API response — **jangan pernah expose `id` integer ke luar sistem**.
3. **Soft Deletes:** Wajib di semua tabel kecuali tabel log (wearable_data, sleep_logs, chat_histories, facial_emotion_logs) yang bersifat immutable.
4. **Enum:** Gunakan `string` + constraint check, atau PostgreSQL native `ENUM` via raw. Pilih `string` + `$casts` untuk fleksibilitas migration.
5. **JSONB bukan JSON:** PostgreSQL mendukung `jsonb` yang bisa di-index. Gunakan `$table->jsonb()` untuk semua kolom JSON.

### Pola UUID di Setiap Tabel

```php
// Pola WAJIB untuk semua tabel
$table->id();                                    // BIGINT AUTOINCREMENT — untuk FK internal
$table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()')); // UUID v4 — expose ke API
```

### Model Trait: `HasPublicId`

```php
// app/Traits/HasPublicId.php
namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasPublicId
{
    // Route model binding pakai uuid bukan id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Semua response API pakai uuid
    public function getPublicId(): string
    {
        return $this->uuid;
    }
}
```

### Model Trait: `HasSoftDeletesAudit`

```php
// Untuk tabel yang perlu soft delete, gunakan kombinasi:
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasPublicId;
```

---

## 🗂️ STRUKTUR DATABASE — MIGRATION LENGKAP

### ERD Summary

```
users (1) ──── (1) user_profiles
users (1) ──── (N) health_screenings
users (1) ──── (N) wearable_data
users (1) ──── (N) sleep_logs
users (1) ──── (N) voice_analyses
users (1) ──── (N) chat_histories
users (1) ──── (N) mental_status_logs
users (1) ──── (N) mood_journals
users (1) ──── (N) notifications
users (1) ──── (N) consultations [as patient_id]
users (1) ──── (N) consultations [as medic_id]
consultations (1) ──── (N) facial_emotion_logs
risk_thresholds (global config, no FK)
```

---

### `users` — Soft Delete ✅ | UUID ✅

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->string('name');
    $table->string('email', 191)->unique();
    $table->string('password');
    $table->string('role', 20)->default('patient');
    // role: 'patient' | 'medic' | 'admin'
    $table->boolean('is_active')->default(true);
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('role');
    $table->index('is_active');
    $table->index(['role', 'is_active']);  // Composite — sering difilter bersamaan
    $table->index('deleted_at');
});
```

---

### `user_profiles` — Soft Delete ✅ | UUID ✅

```php
Schema::create('user_profiles', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('gender', 10)->nullable();  // 'male' | 'female' | 'other'
    $table->date('dob')->nullable();
    $table->string('job', 100)->nullable();
    $table->string('phone', 20)->nullable();
    $table->string('avatar_path')->nullable();
    $table->text('bio')->nullable();
    $table->timestamps();
    $table->softDeletes();
    // user_id sudah UNIQUE — otomatis terindex
});
```

---

### `health_screenings` — Soft Delete ✅ | UUID ✅

```php
Schema::create('health_screenings', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->decimal('height_cm', 5, 2)->nullable();
    $table->decimal('weight_kg', 5, 2)->nullable();
    $table->decimal('bmi', 5, 2)->nullable()
          ->comment('Auto-calculated: weight_kg / (height_m ^ 2)');
    $table->smallInteger('systolic')->nullable()
          ->comment('Tekanan darah sistolik (mmHg)');
    $table->smallInteger('diastolic')->nullable()
          ->comment('Tekanan darah diastolik (mmHg)');
    $table->jsonb('phq9_answers')->nullable()
          ->comment('Array[9] integer 0–3, jawaban PHQ-9');
    $table->smallInteger('phq9_score')->nullable()
          ->comment('Sum phq9_answers (0–27). Auto-calculated.');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['user_id', 'created_at']);  // Query "screening terbaru user X"
    $table->index('phq9_score');               // Filter pasien dengan skor tinggi
});
```

---

### `wearable_data` — Soft Delete ❌ (immutable log) | UUID ✅

```php
Schema::create('wearable_data', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->decimal('hrv_score', 6, 2)->nullable()
          ->comment('Heart Rate Variability dalam ms');
    $table->smallInteger('heart_rate')->nullable()
          ->comment('BPM');
    $table->decimal('stress_index', 5, 2)->nullable()
          ->comment('0–100, derived dari HRV');
    $table->string('device_type', 50)->nullable()
          ->comment('e.g. Garmin, Apple Watch, Simulated');
    $table->boolean('is_simulated')->default(false);
    $table->timestampTz('recorded_at');  // timezone-aware
    $table->timestamps();

    // Indexes — tabel ini akan SANGAT besar, indexing krusial
    $table->index(['user_id', 'recorded_at']);           // Query utama: data user dalam range waktu
    $table->index(['user_id', 'recorded_at', 'hrv_score']); // Covering index untuk dashboard chart
    $table->index('recorded_at');                        // Global query by time range
    $table->index('is_simulated');                       // Filter simulasi vs real
});
```

> **PostgreSQL Tuning Note:** Untuk tabel `wearable_data` yang akan tumbuh besar, pertimbangkan **Table Partitioning by `recorded_at`** (RANGE partitioning per bulan) setelah data melebihi 1 juta baris. Dokumentasikan ini di README.

---

### `sleep_logs` — Soft Delete ❌ (immutable log) | UUID ✅

```php
Schema::create('sleep_logs', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->smallInteger('duration_minutes')->unsigned();
    $table->decimal('quality_score', 4, 2)
          ->comment('0.00–10.00 dari wearable atau self-report');
    $table->string('quality_category', 10)->nullable(); // 'poor'|'fair'|'good'
    $table->time('sleep_time')->nullable();
    $table->time('wake_time')->nullable();
    $table->date('sleep_date');
    $table->timestamps();

    // Unique: 1 log tidur per user per hari
    $table->unique(['user_id', 'sleep_date']);
    // Indexes
    $table->index(['user_id', 'sleep_date']);   // Query history tidur
    $table->index('quality_score');             // Filter tidur buruk
});
```

---

### `voice_analyses` — Soft Delete ✅ | UUID ✅

```php
Schema::create('voice_analyses', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('audio_path')->comment('Path di Laravel Storage');
    $table->decimal('stress_level', 5, 2)->nullable()->comment('0–100');
    $table->string('detected_emotion', 50)->nullable()
          ->comment('calm, anxious, sad, angry, neutral');
    $table->decimal('confidence_score', 4, 3)->nullable()->comment('0.000–1.000');
    $table->jsonb('raw_analysis')->nullable()
          ->comment('Full payload dari AI service (OpenAI/Gemini)');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'created_at']);
    $table->index('stress_level');
});
```

---

### `chat_histories` — Soft Delete ❌ (audit trail) | UUID ✅

```php
Schema::create('chat_histories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('message');
    $table->string('sender_type', 10)->comment("'user' atau 'ai'");
    $table->decimal('sentiment_score', 4, 3)->nullable()
          ->comment('-1.000 (sangat negatif) s.d. 1.000 (sangat positif)');
    $table->string('detected_emotion', 50)->nullable();
    $table->jsonb('context_data')->nullable()
          ->comment('Snapshot HRV, mood, dll saat pesan dikirim');
    $table->boolean('is_flagged')->default(false)
          ->comment('Ditandai oleh dokter untuk review');
    $table->timestamps();

    // Indexes
    $table->index(['user_id', 'created_at']);        // Riwayat chat user
    $table->index(['user_id', 'is_flagged']);         // Flagged messages
    $table->index('sentiment_score');                // Filter negatif
    $table->index(['user_id', 'sender_type', 'created_at']); // Conversation view

    // GIN index untuk full-text search di PostgreSQL
    // Jalankan via raw setelah create table:
    // CREATE INDEX chat_histories_message_gin ON chat_histories USING gin(to_tsvector('indonesian', message));
});
```

> **PostgreSQL Full-Text Search:** Tambahkan GIN index untuk search keyword di chat. Jalankan via `DB::statement()` di migration setelah `Schema::create()`.

```php
// Di akhir migration, setelah Schema::create():
DB::statement("
    CREATE INDEX chat_histories_message_gin
    ON chat_histories
    USING gin(to_tsvector('simple', message))
");
```

---

### `mental_status_logs` — Soft Delete ❌ (audit trail) | UUID ✅

```php
Schema::create('mental_status_logs', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('risk_level', 15)->comment("'low'|'medium'|'high'|'critical'");
    $table->string('detected_emotion', 50)->nullable();
    $table->text('summary_note')->nullable();
    $table->jsonb('contributing_factors')->nullable()
          ->comment('{"hrv": 0.8, "sleep": 0.5, "sentiment": -0.7, "phq9": 0.6}');
    $table->decimal('risk_score', 5, 2)->nullable()->comment('0.00–100.00');
    $table->timestamps();

    // Indexes — tabel ini di-query intensif untuk dashboard
    $table->index(['user_id', 'created_at']);
    $table->index(['user_id', 'risk_level']);
    $table->index('risk_level');                         // Filter global by risk
    $table->index(['risk_level', 'created_at']);         // Dashboard: pasien kritis terbaru
    $table->index('risk_score');                         // Sort by score
});
```

---

### `mood_journals` — Soft Delete ✅ | UUID ✅

```php
Schema::create('mood_journals', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('emoji', 10)->nullable();
    $table->string('mood', 20)->comment("'very_bad'|'bad'|'neutral'|'good'|'very_good'");
    $table->text('note')->nullable();
    $table->date('journal_date');
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['user_id', 'journal_date']);
    $table->index(['user_id', 'journal_date']);
    $table->index('mood');   // Analisis distribusi mood
});
```

---

### `consultations` — Soft Delete ✅ | UUID ✅

```php
Schema::create('consultations', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('medic_id')->constrained('users')->cascadeOnDelete();
    $table->string('session_token', 128)->unique()
          ->comment('Token untuk join video call (Agora/Jitsi/Livekit)');
    $table->string('status', 20)->default('pending')
          ->comment("'pending'|'ongoing'|'completed'|'cancelled'");
    $table->timestampTz('scheduled_at')->nullable();
    $table->timestampTz('started_at')->nullable();
    $table->timestampTz('ended_at')->nullable();
    $table->text('notes')->nullable()->comment('Catatan dokter post-konsultasi');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['patient_id', 'status']);
    $table->index(['medic_id', 'status']);
    $table->index(['medic_id', 'scheduled_at']);   // Calendar view dokter
    $table->index('status');
    $table->index('scheduled_at');
    $table->index('deleted_at');
});
```

---

### `facial_emotion_logs` — Soft Delete ❌ | UUID ✅

```php
Schema::create('facial_emotion_logs', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
    $table->string('detected_mood', 50);
    $table->decimal('confidence', 4, 3)->nullable()->comment('0.000–1.000');
    $table->jsonb('emotion_breakdown')->nullable()
          ->comment('{"happy": 0.1, "sad": 0.6, "anxious": 0.3}');
    $table->timestampTz('captured_at');
    $table->timestamps();

    $table->index(['consultation_id', 'captured_at']);
    $table->index('captured_at');

    // GIN index untuk query di dalam emotion_breakdown JSONB
    // DB::statement("CREATE INDEX facial_emotion_breakdown_gin ON facial_emotion_logs USING gin(emotion_breakdown)");
});
```

---

### `risk_thresholds` — Soft Delete ✅ | UUID ✅

```php
Schema::create('risk_thresholds', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->string('parameter_name', 50)->unique()
          ->comment('hrv | sleep_duration | sentiment_score | phq9_score | stress_index');
    $table->decimal('low_min', 8, 3)->nullable();
    $table->decimal('low_max', 8, 3)->nullable();
    $table->decimal('medium_min', 8, 3)->nullable();
    $table->decimal('medium_max', 8, 3)->nullable();
    $table->decimal('high_min', 8, 3)->nullable();
    $table->decimal('high_max', 8, 3)->nullable();
    $table->decimal('weight', 4, 3)->default(0.25)
          ->comment('Bobot parameter dalam kalkulasi risk score (total harus = 1.0)');
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index('is_active');
});
```

---

### `notifications` — Soft Delete ✅ | UUID ✅

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'));
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title', 150);
    $table->text('message');
    $table->string('type', 20)->default('info')
          ->comment("'info'|'warning'|'critical'|'reminder'");
    $table->boolean('is_read')->default(false);
    $table->jsonb('action_data')->nullable()
          ->comment('Deep link atau action payload untuk mobile');
    $table->timestamps();
    $table->softDeletes();

    // Indexes — notifikasi di-query sangat sering
    $table->index(['user_id', 'is_read']);
    $table->index(['user_id', 'type', 'is_read']);
    $table->index(['user_id', 'created_at']);
    $table->index('type');
});
```

---

## 🏗️ ARSITEKTUR PROJECT LARAVEL

### Inertia.js Setup

HeaLink menggunakan **Inertia.js v2** sebagai "glue" antara Laravel controller dan React component. Controller me-return `Inertia::render('PageName', $props)`, bukan `view()`.

```php
// Contoh controller dengan Inertia
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Dashboard/Index', [
            'patients' => PatientResource::collection(
                $this->patientRepo->getPatientsWithLatestRisk()
            ),
            'stats' => $this->getDashboardStats(),
        ]);
    }
}
```

### Struktur Direktori

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Web/                        # Inertia controllers (return Inertia::render)
│   │   │   ├── DashboardController.php
│   │   │   ├── PatientController.php
│   │   │   ├── ConsultationController.php
│   │   │   ├── ReportController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── RiskController.php
│   │   │   └── Admin/
│   │   │       ├── UserManagementController.php
│   │   │       └── StatisticsController.php
│   │   ├── Api/                        # REST API untuk Flutter mobile
│   │   │   ├── AuthController.php
│   │   │   ├── VitalController.php
│   │   │   ├── ChatController.php
│   │   │   ├── ScreeningController.php
│   │   │   ├── SleepController.php
│   │   │   ├── MoodJournalController.php
│   │   │   ├── TeleconsultController.php
│   │   │   └── NotificationController.php
│   ├── Middleware/
│   │   ├── RoleMiddleware.php
│   │   └── HandleInertiaRequests.php   # Share global props ke semua page React
│   └── Requests/
│       ├── Api/
│       │   ├── SyncVitalRequest.php
│       │   ├── StoreChatRequest.php
│       │   └── StoreScreeningRequest.php
│       └── Web/
│           ├── StoreConsultationRequest.php
│           └── UpdateRiskThresholdRequest.php
├── Services/
│   ├── RiskScoringService.php
│   ├── ChatAnalysisService.php
│   ├── WearableSimulatorService.php
│   ├── NotificationService.php
│   └── ReportGeneratorService.php
├── Repositories/
│   ├── PatientRepository.php
│   ├── VitalRepository.php
│   └── ChatRepository.php
├── Models/
│   ├── User.php
│   ├── UserProfile.php
│   ├── HealthScreening.php
│   ├── WearableData.php
│   ├── SleepLog.php
│   ├── ChatHistory.php
│   ├── MentalStatusLog.php
│   ├── Consultation.php
│   ├── FacialEmotionLog.php
│   ├── RiskThreshold.php
│   ├── Notification.php
│   └── MoodJournal.php
├── Http/Resources/                     # JsonResource — expose UUID, bukan id
│   ├── UserResource.php
│   ├── PatientResource.php
│   ├── WearableDataResource.php
│   ├── ChatHistoryResource.php
│   └── ConsultationResource.php
├── Traits/
│   └── HasPublicId.php
├── Events/
│   ├── NewSentimentAlert.php
│   ├── PatientRiskElevated.php
│   └── VitalDataSynced.php
├── Listeners/
│   ├── TriggerRiskAssessment.php
│   └── SendDoctorNotification.php
├── Jobs/
│   ├── AnalyzeChatSentimentJob.php
│   ├── ProcessVoiceAnalysisJob.php
│   └── GenerateDailyRiskReportJob.php
└── Policies/
    ├── PatientPolicy.php
    └── ConsultationPolicy.php

resources/
└── js/
    ├── Components/                     # Reusable shadcn/ui components
    │   ├── ui/                         # shadcn generated (jangan edit manual)
    │   ├── AppSidebar.tsx
    │   ├── PatientCard.tsx
    │   ├── RiskBadge.tsx
    │   ├── HrvChart.tsx
    │   ├── SleepChart.tsx
    │   └── RealTimeAlert.tsx
    ├── Layouts/
    │   ├── AppLayout.tsx               # Layout utama dengan sidebar
    │   └── AuthLayout.tsx
    ├── Pages/                          # 1 file = 1 Inertia page = 1 route
    │   ├── Dashboard/
    │   │   └── Index.tsx
    │   ├── Patients/
    │   │   ├── Index.tsx
    │   │   ├── Show.tsx                # Deep analytics
    │   │   └── ChatLog.tsx
    │   ├── Consultations/
    │   │   ├── Index.tsx               # Scheduling
    │   │   └── Room.tsx                # Doctor console
    │   ├── Notifications/
    │   │   └── Index.tsx
    │   ├── Risk/
    │   │   └── Dashboard.tsx
    │   ├── Reports/
    │   │   └── Index.tsx
    │   └── Admin/
    │       ├── Users.tsx
    │       └── Statistics.tsx
    ├── hooks/
    │   ├── useRealTime.ts              # Echo/Reverb subscription
    │   └── usePatientData.ts
    └── types/
        └── index.d.ts                  # TypeScript types matching Laravel models
```

---

## 🎨 FRONTEND — REACT + SHADCN/UI

### Prinsip Desain

- **Design System:** shadcn/ui + Tailwind CSS v4 — JANGAN gunakan komponen UI library lain (MUI, Ant Design, dll)
- **Tone Visual:** Clean, clinical, trustworthy — bukan playful. Palette dominan slate/zinc dengan aksen biru medis.
- **Dark Mode:** Wajib support via shadcn/ui `ThemeProvider`
- **Typography:** Gunakan `Geist` atau `Inter` dari Google Fonts — konsisten seluruh app
- **Icon:** `lucide-react` — sudah bundled dengan shadcn/ui
- **Chart:** `recharts` untuk semua grafik (HRV, tidur, mood, statistik)
- **State Management:** React Query (TanStack Query) untuk server state, Zustand untuk UI state lokal jika diperlukan

### Komponen shadcn yang Wajib Diinstall

```bash
npx shadcn@latest add button card badge table dialog sheet
npx shadcn@latest add form input select textarea label
npx shadcn@latest add dropdown-menu avatar separator
npx shadcn@latest add alert alert-dialog toast
npx shadcn@latest add tabs scroll-area skeleton
npx shadcn@latest add calendar date-picker
npx shadcn@latest add sidebar navigation-menu breadcrumb
npx shadcn@latest add progress chart  # shadcn chart = recharts wrapper
```

### Contoh Halaman React (Pattern)

```tsx
// resources/js/Pages/Dashboard/Index.tsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import RiskBadge from '@/Components/RiskBadge';
import type { Patient, PageProps } from '@/types';

interface Props extends PageProps {
    patients: Patient[];
    stats: {
        total: number;
        high_risk: number;
        critical: number;
    };
}

export default function Dashboard({ patients, stats }: Props) {
    return (
        <AppLayout>
            <Head title="Dashboard — HeaLink" />

            <div className="mb-6 grid grid-cols-3 gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Total Pasien</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">{stats.total}</p>
                    </CardContent>
                </Card>
                {/* ... */}
            </div>

            {/* Patient table dengan shadcn Table */}
        </AppLayout>
    );
}
```

### TypeScript Types (Wajib di `types/index.d.ts`)

```ts
export interface User {
    uuid: string; // Selalu gunakan uuid di frontend, BUKAN id
    name: string;
    email: string;
    role: 'patient' | 'medic' | 'admin';
    is_active: boolean;
}

export interface Patient extends User {
    profile: UserProfile | null;
    latest_mental_status: MentalStatusLog | null;
    latest_wearable: WearableData | null;
}

export interface MentalStatusLog {
    uuid: string;
    risk_level: 'low' | 'medium' | 'high' | 'critical';
    risk_score: number;
    detected_emotion: string | null;
    created_at: string;
}

export interface WearableData {
    uuid: string;
    hrv_score: number | null;
    heart_rate: number | null;
    stress_index: number | null;
    recorded_at: string;
}

// ... dan seterusnya untuk semua entity
```

---

## 🔌 API ENDPOINTS (Mobile Flutter)

> Semua response API menggunakan `uuid` — **JANGAN expose `id` integer**.

### Auth

```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me
PUT    /api/v1/auth/profile
```

### Vitals & Wearable

```
POST   /api/v1/vitals/sync          → Simpan HRV, heart_rate
GET    /api/v1/vitals/latest        → Data terbaru
GET    /api/v1/vitals/history       → ?from=&to= (date range)
POST   /api/v1/sleep                → Simpan sleep log
GET    /api/v1/sleep/history
```

### Screening

```
PUT    /api/v1/screening            → Upsert health screening + hitung BMI & PHQ-9 otomatis
GET    /api/v1/screening/latest
```

### Chat & AI

```
POST   /api/v1/chat                 → Kirim pesan, trigger sentiment job
GET    /api/v1/chat/history         → Paginated
POST   /api/v1/voice/analyze        → Upload audio multipart/form-data
```

### Mood Journal

```
POST   /api/v1/mood
GET    /api/v1/mood                 → ?date=&from=&to=
```

### Telemedicine

```
GET    /api/v1/consultations/{uuid}/session   → Dapatkan token video call
POST   /api/v1/consultations/{uuid}/start
PUT    /api/v1/consultations/{uuid}/end
POST   /api/v1/consultations/{uuid}/facial-emotion
```

### Notifications

```
GET    /api/v1/notifications
PUT    /api/v1/notifications/{uuid}/read
PUT    /api/v1/notifications/read-all
```

### API Response Format — WAJIB KONSISTEN

```json
{
  "success": true,
  "message": "Data berhasil disimpan",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "..."
  },
  "meta": {
    "timestamp": "2026-01-01T00:00:00.000Z"
  }
}
```

```json
// Error format
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "email": ["Email sudah terdaftar"]
    },
    "meta": {
        "timestamp": "2026-01-01T00:00:00.000Z"
    }
}
```

### JsonResource Pattern — UUID Only

```php
// app/Http/Resources/WearableDataResource.php
class WearableDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,   // ✅ expose uuid
            // 'id'        => $this->id,      // ❌ JANGAN expose id integer
            'hrv_score'    => $this->hrv_score,
            'heart_rate'   => $this->heart_rate,
            'stress_index' => $this->stress_index,
            'device_type'  => $this->device_type,
            'is_simulated' => $this->is_simulated,
            'recorded_at'  => $this->recorded_at?->toIso8601String(),
        ];
    }
}
```

---

## ⚙️ MODEL — RELATIONSHIPS & CASTS

### `User.php`

```php
class User extends Authenticatable
{
    use HasPublicId, SoftDeletes, HasFactory, HasApiTokens;

    protected $guarded = ['id'];
    protected $hidden = ['id', 'password', 'remember_token']; // sembunyikan id & password

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
        ];
    }

    // Relationships
    public function profile(): HasOne { return $this->hasOne(UserProfile::class); }
    public function healthScreenings(): HasMany { return $this->hasMany(HealthScreening::class); }
    public function wearableData(): HasMany { return $this->hasMany(WearableData::class); }
    public function sleepLogs(): HasMany { return $this->hasMany(SleepLog::class); }
    public function chatHistories(): HasMany { return $this->hasMany(ChatHistory::class); }
    public function mentalStatusLogs(): HasMany { return $this->hasMany(MentalStatusLog::class); }
    public function moodJournals(): HasMany { return $this->hasMany(MoodJournal::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function consultationsAsPatient(): HasMany {
        return $this->hasMany(Consultation::class, 'patient_id');
    }
    public function consultationsAsMedic(): HasMany {
        return $this->hasMany(Consultation::class, 'medic_id');
    }

    // Scoped relationships untuk query efisien
    public function latestMentalStatus(): HasOne {
        return $this->hasOne(MentalStatusLog::class)->latestOfMany();
    }
    public function latestWearable(): HasOne {
        return $this->hasOne(WearableData::class)->latestOfMany('recorded_at');
    }
    public function latestScreening(): HasOne {
        return $this->hasOne(HealthScreening::class)->latestOfMany();
    }
}
```

---

## ⚙️ SERVICE LAYER

### `RiskScoringService.php`

```php
class RiskScoringService
{
    public function calculateAndPersist(User $user): MentalStatusLog
    {
        // Ambil thresholds dari cache (di-cache 1 jam)
        $thresholds = cache()->remember('risk_thresholds', 3600, fn() =>
            RiskThreshold::where('is_active', true)->get()->keyBy('parameter_name')
        );

        $factors = $this->gatherFactors($user);
        $score = $this->computeWeightedScore($factors, $thresholds);
        $riskLevel = $this->scoreToLevel($score);

        return MentalStatusLog::create([
            'user_id'             => $user->id,
            'risk_level'          => $riskLevel,
            'risk_score'          => $score,
            'contributing_factors' => $factors,
            'detected_emotion'    => $factors['latest_emotion'] ?? null,
            'summary_note'        => $this->generateSummary($factors, $riskLevel),
        ]);
    }

    private function gatherFactors(User $user): array
    {
        $hrv       = $user->latestWearable?->hrv_score;
        $sleep     = $user->sleepLogs()->latest('sleep_date')->value('quality_score');
        $sentiment = $user->chatHistories()->latest()->limit(5)->avg('sentiment_score');
        $phq9      = $user->latestScreening?->phq9_score;

        return compact('hrv', 'sleep', 'sentiment', 'phq9');
    }

    private function scoreToLevel(float $score): string
    {
        return match(true) {
            $score >= 81 => 'critical',
            $score >= 61 => 'high',
            $score >= 31 => 'medium',
            default      => 'low',
        };
    }
}
```

### `WearableSimulatorService.php`

```php
class WearableSimulatorService
{
    // Generate realistic-ish HRV data dengan fluktuasi natural
    public function generateForUser(User $user): WearableData
    {
        $hour   = (int) now()->format('G');
        // HRV lebih tinggi pagi hari, lebih rendah sore (natural circadian rhythm)
        $base   = 50 + (10 * cos(($hour - 6) * M_PI / 12));
        $noise  = (mt_rand(-150, 150) / 10);  // ±15ms noise
        $hrv    = max(15.0, min(100.0, $base + $noise));
        $hr     = (int) (75 - ($hrv * 0.3) + mt_rand(-5, 5));

        return WearableData::create([
            'user_id'      => $user->id,
            'hrv_score'    => round($hrv, 2),
            'heart_rate'   => max(50, min(110, $hr)),
            'stress_index' => round(100 - $hrv, 2),
            'device_type'  => 'Simulated',
            'is_simulated' => true,
            'recorded_at'  => now(),
        ]);
    }
}
```

---

## 📡 REAL-TIME (LARAVEL REVERB + ECHO)

### Broadcasting Channels

```php
// routes/channels.php
Broadcast::channel('doctor.{doctorUuid}', function (User $user, string $doctorUuid) {
    // Auth by UUID
    return $user->uuid === $doctorUuid && $user->role === 'medic';
});

Broadcast::channel('consultation.{consultationUuid}', function (User $user, string $consultationUuid) {
    $consultation = Consultation::where('uuid', $consultationUuid)->first();
    return $consultation && in_array($user->id, [$consultation->patient_id, $consultation->medic_id]);
});
```

### Frontend Echo Setup

```ts
// resources/js/hooks/useRealTime.ts
import Echo from 'laravel-echo';

// Listen notifikasi risiko pasien naik
export function usePatientRiskAlert(
    doctorUuid: string,
    onAlert: (data: any) => void,
) {
    useEffect(() => {
        const channel = window.Echo.private(`doctor.${doctorUuid}`);
        channel.listen('PatientRiskElevated', onAlert);
        return () => channel.stopListening('PatientRiskElevated');
    }, [doctorUuid]);
}
```

---

## 🔒 AUTHORIZATION

### RoleMiddleware

```php
// Daftarkan di bootstrap/app.php (Laravel 13 style, tanpa Kernel)
$middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
```

```php
// app/Http/Middleware/RoleMiddleware.php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (!auth()->check() || !in_array(auth()->user()->role, $roles)) {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        abort(403);
    }
    return $next($request);
}
```

### Web Routes (Laravel 13, tanpa RouteServiceProvider)

```php
// routes/web.php
Route::middleware(['auth', 'role:medic,admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{user:uuid}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{user:uuid}/chat-log', [PatientController::class, 'chatLog']);
    Route::get('/consultations', [ConsultationController::class, 'index']);
    Route::get('/consultations/{consultation:uuid}/room', [ConsultationController::class, 'room']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/risk', [RiskController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'index']);
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index']);
    Route::get('/admin/statistics', [StatisticsController::class, 'index']);
    Route::apiResource('/admin/risk-thresholds', RiskThresholdController::class);
});
```

> **Laravel 13 note:** Route model binding `{user:uuid}` langsung bekerja tanpa setup tambahan karena `getRouteKeyName()` sudah diset ke `uuid` via trait `HasPublicId`.

---

## 📦 PACKAGES

### Composer (Backend)

```json
{
    "require": {
        "laravel/sanctum": "^4.x",
        "laravel/reverb": "^2.x",
        "inertiajs/inertia-laravel": "^2.x",
        "barryvdh/laravel-dompdf": "^3.x",
        "maatwebsite/excel": "^3.1",
        "openai-php/laravel": "^0.10",
        "tightenco/ziggy": "^2.x"
    },
    "require-dev": {
        "pestphp/pest": "^3.x",
        "pestphp/pest-plugin-laravel": "^3.x",
        "fakerphp/faker": "^1.23"
    }
}
```

### NPM (Frontend)

```json
{
    "dependencies": {
        "@inertiajs/react": "^2.x",
        "react": "^19.x",
        "react-dom": "^19.x",
        "@tanstack/react-query": "^5.x",
        "recharts": "^2.x",
        "laravel-echo": "^2.x",
        "pusher-js": "^8.x",
        "lucide-react": "latest",
        "class-variance-authority": "latest",
        "clsx": "latest",
        "tailwind-merge": "latest",
        "tailwindcss-animate": "latest",
        "zustand": "^5.x",
        "date-fns": "^3.x"
    },
    "devDependencies": {
        "@types/react": "^19.x",
        "@types/react-dom": "^19.x",
        "typescript": "^5.x",
        "tailwindcss": "^4.x",
        "vite": "^6.x",
        "@vitejs/plugin-react": "latest"
    }
}
```

---

## ✅ ATURAN CODING — WAJIB DIIKUTI AI AGENT

### Backend Rules

1. **UUID di API, id di internal.** Semua API response dan route parameter gunakan `uuid`. FK relasi database tetap pakai `id` (bigint) untuk performa.
2. **FormRequest wajib** — validasi TIDAK boleh ada di Controller.
3. **Service layer wajib** — business logic TIDAK boleh ada di Controller. Controller hanya: validate → call service → return response.
4. **Repository pattern** — database query kompleks dipindah ke Repository, bukan di Controller atau Service.
5. **Soft delete** sesuai tabel yang sudah ditentukan. Jangan tambah soft delete ke tabel log.
6. **Cache thresholds** — `risk_thresholds` selalu di-cache, jangan query DB setiap request.
7. **JSONB di PostgreSQL** — gunakan `->jsonb()` bukan `->json()` di migration.
8. **Decimal precision** — angka medis (BMI, HRV, sentiment) gunakan `decimal(x,y)` bukan `float` untuk presisi konsisten.
9. **timestampTz** — semua kolom waktu gunakan `timestampTz` (timezone-aware), bukan `timestamp`.
10. **Eager loading** — selalu gunakan `with()` untuk relasi yang akan ditampilkan. JANGAN N+1 query.

### Frontend Rules

1. **Gunakan shadcn/ui** — jangan buat komponen UI dari nol jika sudah ada di shadcn.
2. **UUID di semua props** — TypeScript types tidak boleh punya field `id: number`. Selalu `uuid: string`.
3. **Inertia Link** untuk navigasi internal — jangan `<a href>` langsung.
4. **React Query** untuk data fetching yang butuh polling/refetch — jangan `useEffect + fetch` manual.
5. **TypeScript strict** — tidak boleh ada `any`. Definisikan semua type di `types/index.d.ts`.
6. **Komponen kecil** — satu file maksimal 200 baris. Pecah menjadi komponen jika lebih.

### Naming Conventions

| Type            | Pattern                | Contoh                    |
| --------------- | ---------------------- | ------------------------- |
| Controller      | `NounController`       | `PatientController`       |
| Service         | `NounVerbService`      | `RiskScoringService`      |
| Repository      | `NounRepository`       | `PatientRepository`       |
| Job             | `VerbNounJob`          | `AnalyzeChatSentimentJob` |
| Event           | `NounVerbed`           | `PatientRiskElevated`     |
| Request         | `ActionNounRequest`    | `StoreChatRequest`        |
| Resource        | `NounResource`         | `WearableDataResource`    |
| React Page      | `PascalCase/Index.tsx` | `Dashboard/Index.tsx`     |
| React Component | `PascalCase.tsx`       | `RiskBadge.tsx`           |

---

## 🚀 SEEDER & FACTORY

```
RiskThresholdSeeder  → Threshold default 5 parameter (run pertama kali)
UserSeeder           → 1 admin, 3 dokter, 20 pasien (dengan profil)
HealthScreeningSeeder→ 1 screening per pasien (BMI & PHQ-9 calculated)
WearableSeeder       → 30 hari × 24 data per pasien (simulated, fluktuatif)
SleepSeeder          → 30 hari per pasien
ChatSeeder           → 10–20 pesan per pasien (mix sentiment)
MoodJournalSeeder    → 30 hari per pasien
MentalStatusSeeder   → Recalculate dari data seeder (jalankan setelah semua seeder)
ConsultationSeeder   → 3–5 konsultasi per pasien (mix status)
```

---

## 🧪 TESTING

```
tests/
├── Feature/
│   ├── Api/
│   │   ├── Auth/LoginTest.php
│   │   ├── Vitals/SyncVitalTest.php
│   │   └── Chat/ChatAnalysisTest.php
│   └── Web/
│       ├── DashboardTest.php
│       └── PatientDetailTest.php
└── Unit/
    ├── Services/RiskScoringServiceTest.php
    └── Services/WearableSimulatorServiceTest.php
```

---

## 🎯 URUTAN PENGERJAAN

### Phase 1 — Foundation ✅

- [x] Install semua shadcn components yang dibutuhkan
- [x] Buat trait `HasPublicId`
- [x] Buat semua migrations (urutan: no-FK dulu → FK setelahnya)
- [x] Jalankan `DB::statement()` untuk GIN indexes setelah migration
- [x] Buat semua Models + Relationships + Casts + Traits
- [x] Buat `RoleMiddleware` dan daftarkan di `bootstrap/app.php`
- [x] Setup Sanctum untuk API
- [x] Buat semua Factory & Seeder
- [x] Jalankan `migrate:fresh --seed`, verifikasi data via Tinker

### Phase 2 — API Core (Mobile)

- [x] `AuthController` — register, login, logout, me
- [x] `VitalController` — sync & history
- [x] `ScreeningController` — upsert + auto-calculate BMI & PHQ-9
- [x] `ChatController` — store message (sync, job nanti)
- [x] `SleepController` — store & history
- [x] `MoodJournalController` — store & history
- [x] `NotificationController` — list & mark read
- [x] Semua `JsonResource` (pastikan UUID only)
- [x] Test semua endpoint di Postman + tulis Pest tests

### Phase 3 — Business Logic

- [x] `RiskScoringService` + unit test
- [x] `WearableSimulatorService` + unit test
- [x] Setup Redis Queue
- [x] `AnalyzeChatSentimentJob` (OpenAI integration via `ChatAIProviderInterface`)
- [x] `ProcessVoiceAnalysisJob`
- [x] Events + Listeners (risk scoring trigger)
- [x] Setup Laravel Reverb + broadcasting channels
- [x] `NotificationService`

### Phase 4 — Web Dashboard (Inertia + React)

- [x] `AppLayout.tsx` — sidebar, nav, user menu (shadcn Sidebar component)
- [x] `AuthLayout.tsx` — login page
- [x] `Dashboard/Index.tsx` — patient table + stats cards
- [x] `Patients/Show.tsx` — deep analytics dengan charts (recharts)
- [x] `Patients/ChatLog.tsx` — chat history dengan highlight
- [x] `Consultations/Room.tsx` — doctor console telemedicine
- [x] `Notifications/Index.tsx` — list + intervensi
- [x] `Risk/Dashboard.tsx` — gauge + breakdown
- [x] `Consultations/Index.tsx` — scheduling/calendar
- [x] `Reports/Index.tsx` — export PDF/Excel
- [x] `Admin/Users.tsx` + `Admin/Statistics.tsx`

### Phase 5 — Polish

- [ ] Empty states, loading skeletons (shadcn `Skeleton`)
- [ ] Error boundaries di React
- [ ] Auto-refresh real-time via Reverb/Echo
- [ ] TypeScript types audit (hapus semua `any`)
- [ ] Pest feature tests untuk semua web routes
- [ ] PostgreSQL query review (EXPLAIN ANALYZE semua query berat)

---

## 💡 CATATAN KHUSUS UNTUK AI AGENT

> **Baca ini sebelum menulis kode apapun.**

1. **Nama aplikasi adalah HeaLink** — bukan MindCare, bukan nama lain.

2. **Laravel 13 tidak punya `Kernel.php` untuk HTTP** — middleware didaftarkan di `bootstrap/app.php` menggunakan `$middleware->alias()`.

3. **Inertia.js menggantikan Blade** — jangan gunakan `return view('...')` untuk web routes. Selalu `return Inertia::render('Page/Component', $props)`.

4. **UUID expose, id internal** — ini aturan absolut. Jika ragu, default ke UUID. GIN index di `uuid` column sudah di-handle oleh `UNIQUE` constraint yang otomatis membuat B-tree index.

5. **JSONB di PostgreSQL** — selalu gunakan `->jsonb()` bukan `->json()`. JSONB bisa di-index dengan GIN, lebih efisien untuk query.

6. **Data wearable adalah time-series** — untuk query `wearable_data` yang besar, selalu gunakan index `['user_id', 'recorded_at']` dan batasi dengan `whereBetween` atau `take()`. Hindari full table scan.

7. **AI bersifat modular** — implement `interface ChatAIProviderInterface` dengan method `analyze(string $message, array $context): array`. Buat `OpenAIProvider` sebagai default implementasi. Ini memungkinkan switch ke Gemini/Claude tanpa refactor.

8. **Telemedicine video** — backend hanya generate token dan simpan log. Gunakan Agora/Jitsi/Livekit. Jangan implement video server.

9. **Deteksi wajah** — backend hanya menerima payload `detected_mood` dari mobile. Backend tidak memproses gambar.

10. **Semua timestamp UTC** — simpan dalam UTC, tampilkan di React dalam timezone lokal via `date-fns` atau `Intl.DateTimeFormat`.

11. **PHQ-9 auto-calculate** — saat screening disimpan, hitung otomatis `phq9_score = sum(phq9_answers)` di Service/Observer, bukan di migration.

12. **Soft delete + UUID route binding** — pastikan scope query soft delete aktif. Route model binding `{model:uuid}` otomatis menghormati soft delete karena Eloquent default exclude `deleted_at IS NOT NULL`.

---

_Dokumen ini adalah living document. Update setiap kali ada perubahan arsitektur._
_Versi: 2.0 | Stack: Laravel 13 / PHP 8.4 / React 19 / PostgreSQL 16 / shadcn/ui_
