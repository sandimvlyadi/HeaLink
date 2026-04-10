import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Brain,
    HeartPulse,
    MessageSquare,
    ShieldCheck,
    Video,
} from 'lucide-react';

const features = [
    {
        icon: Brain,
        title: 'Pemantauan Risiko Mental',
        description:
            'Deteksi dini risiko kesehatan mental pasien secara real-time berdasarkan data wearable, PHQ-9, dan analisis sentimen chat.',
    },
    {
        icon: HeartPulse,
        title: 'Integrasi Data Wearable',
        description:
            'Sinkronisasi otomatis data HRV, detak jantung, dan indeks stres dari perangkat wearable pasien.',
    },
    {
        icon: MessageSquare,
        title: 'Analisis Chat AI',
        description:
            'Setiap percakapan pasien dianalisis sentimen dan emosinya oleh AI untuk mendeteksi tanda-tanda awal krisis.',
    },
    {
        icon: Video,
        title: 'Telemedicine',
        description:
            'Sesi konsultasi video langsung antara dokter dan pasien dalam satu platform terintegrasi.',
    },
    {
        icon: Activity,
        title: 'Dashboard Medis',
        description:
            'Pantau semua pasien, statistik risiko harian, dan antrean konsultasi dalam satu tampilan terpusat.',
    },
    {
        icon: ShieldCheck,
        title: 'Notifikasi Peringatan',
        description:
            'Dokter menerima alert real-time saat status risiko pasien meningkat ke level tinggi atau kritis.',
    },
];

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth, currentTeam } = usePage().props;
    const dashboardUrl = currentTeam ? dashboard() : '/dashboard';

    return (
        <>
            <Head title="HeaLink — Platform Kesehatan Mental Digital" />

            <div className="min-h-screen bg-background text-foreground">
                {/* Navbar */}
                <header className="fixed top-0 right-0 left-0 z-50 border-b bg-background/80 backdrop-blur-sm">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                        <div className="flex items-center gap-2">
                            <div className="flex size-8 items-center justify-center rounded-lg">
                                <AppLogoIcon className="size-8" />
                            </div>
                            <span className="text-lg font-bold tracking-tight">
                                HeaLink
                            </span>
                        </div>

                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Button asChild size="sm">
                                    <Link href={dashboardUrl}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={login()}>Masuk</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button asChild size="sm">
                                            <Link href={register()}>
                                                Daftar
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    {/* Hero */}
                    <section className="flex min-h-screen flex-col items-center justify-center px-6 pt-16 text-center">
                        <div className="mx-auto max-w-3xl">
                            <div className="mb-6 inline-flex items-center gap-2 rounded-full border bg-muted px-4 py-1.5 text-sm text-muted-foreground">
                                <span className="size-2 animate-pulse rounded-full bg-emerald-500" />
                                Platform Kesehatan Mental Digital
                            </div>

                            <h1 className="mb-6 text-5xl leading-tight font-bold tracking-tight lg:text-6xl">
                                Pantau Kesehatan Mental
                                <br />
                                <span className="text-primary">
                                    Pasien Anda
                                </span>{' '}
                                Secara Real-Time
                            </h1>

                            <p className="mx-auto mb-10 max-w-xl text-lg text-muted-foreground">
                                HeaLink adalah platform terintegrasi untuk
                                dokter dan tenaga medis dalam memantau,
                                menganalisis, dan mengintervensi kesehatan
                                mental pasien berbasis data.
                            </p>

                            <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                {auth.user ? (
                                    <Button asChild size="lg">
                                        <Link href={dashboardUrl}>
                                            Buka Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        {canRegister && (
                                            <Button asChild size="lg">
                                                <Link href={register()}>
                                                    Mulai Sekarang
                                                </Link>
                                            </Button>
                                        )}
                                        <Button
                                            asChild
                                            variant="ghost"
                                            size="lg"
                                        >
                                            <Link href={login()}>
                                                Masuk ke Akun
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Stats */}
                        <div className="mx-auto mt-20 grid max-w-2xl grid-cols-3 gap-8 border-t pt-12">
                            {[
                                { value: 'Real-Time', label: 'Monitoring' },
                                { value: 'AI-Powered', label: 'Analisis Chat' },
                                {
                                    value: 'Multi-Role',
                                    label: 'Akses Platform',
                                },
                            ].map((stat) => (
                                <div key={stat.label} className="text-center">
                                    <p className="text-2xl font-bold">
                                        {stat.value}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {stat.label}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* Features */}
                    <section className="bg-muted/30 px-6 py-24">
                        <div className="mx-auto max-w-6xl">
                            <div className="mb-16 text-center">
                                <h2 className="mb-4 text-3xl font-bold tracking-tight">
                                    Semua yang Dibutuhkan Tim Medis
                                </h2>
                                <p className="mx-auto max-w-lg text-muted-foreground">
                                    Dari deteksi risiko hingga konsultasi video,
                                    HeaLink menyatukan seluruh alur kerja
                                    kesehatan mental dalam satu platform.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature) => (
                                    <div
                                        key={feature.title}
                                        className="rounded-xl border bg-background p-6 transition-shadow hover:shadow-md"
                                    >
                                        <div className="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10">
                                            <feature.icon className="size-5 text-primary" />
                                        </div>
                                        <h3 className="mb-2 font-semibold">
                                            {feature.title}
                                        </h3>
                                        <p className="text-sm leading-relaxed text-muted-foreground">
                                            {feature.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Roles */}
                    <section className="px-6 py-24">
                        <div className="mx-auto max-w-4xl">
                            <div className="mb-16 text-center">
                                <h2 className="mb-4 text-3xl font-bold tracking-tight">
                                    Dirancang untuk Setiap Peran
                                </h2>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                {[
                                    {
                                        role: 'Dokter / Medic',
                                        color: 'border-blue-200 bg-blue-50 dark:border-blue-900/40 dark:bg-blue-950/20',
                                        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                                        items: [
                                            'Dashboard pasien aktif',
                                            'Monitor risiko mental real-time',
                                            'Chat log & analisis sentimen',
                                            'Jadwal & ruang konsultasi video',
                                            'Notifikasi alert kritis',
                                        ],
                                    },
                                    {
                                        role: 'Admin',
                                        color: 'border-violet-200 bg-violet-50 dark:border-violet-900/40 dark:bg-violet-950/20',
                                        badge: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400',
                                        items: [
                                            'Manajemen pengguna & akses',
                                            'Statistik global platform',
                                            'Konfigurasi ambang batas risiko',
                                            'Laporan performa sistem',
                                            'Audit log aktivitas',
                                        ],
                                    },
                                ].map((item) => (
                                    <div
                                        key={item.role}
                                        className={`rounded-xl border p-6 ${item.color}`}
                                    >
                                        <span
                                            className={`mb-4 inline-block rounded-full px-3 py-1 text-xs font-medium ${item.badge}`}
                                        >
                                            {item.role}
                                        </span>
                                        <ul className="space-y-2">
                                            {item.items.map((point) => (
                                                <li
                                                    key={point}
                                                    className="flex items-start gap-2 text-sm"
                                                >
                                                    <span className="mt-0.5 text-primary">
                                                        ✓
                                                    </span>
                                                    {point}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* CTA */}
                    {!auth.user && (
                        <section className="bg-primary px-6 py-20 text-primary-foreground">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="mb-4 text-3xl font-bold">
                                    Siap Memulai?
                                </h2>
                                <p className="mb-8 text-primary-foreground/80">
                                    Bergabung dengan platform HeaLink dan mulai
                                    pantau kesehatan mental pasien Anda hari
                                    ini.
                                </p>
                                <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                    {canRegister && (
                                        <Button
                                            asChild
                                            size="lg"
                                            variant="secondary"
                                        >
                                            <Link href={register()}>
                                                Buat Akun
                                            </Link>
                                        </Button>
                                    )}
                                    <Button asChild size="lg" variant="ghost">
                                        <Link href={login()}>
                                            Masuk ke Akun
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </section>
                    )}
                </main>

                {/* Footer */}
                <footer className="border-t px-6 py-8">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 text-sm text-muted-foreground sm:flex-row">
                        <div className="flex items-center gap-2">
                            <div className="flex size-6 items-center justify-center rounded">
                                <AppLogoIcon className="size-6" />
                            </div>
                            <span className="font-medium text-foreground">
                                HeaLink
                            </span>
                            <span>— Platform Kesehatan Mental Digital</span>
                        </div>
                        <span>
                            © {new Date().getFullYear()} HeaLink. All rights
                            reserved.
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
