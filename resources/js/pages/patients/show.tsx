import {
    chatLog,
    index as patientsIndex,
} from '@/actions/App/Http/Controllers/Web/PatientController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { MentalStatusLog, Patient, SleepLog, WearableData } from '@/types';
import { Deferred, Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    Brain,
    Heart,
    MessageCircle,
    Moon,
    TrendingDown,
    TrendingUp,
    User,
    Zap,
} from 'lucide-react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Props {
    patient: Patient;
    // Deferred — not present in initial page payload
    wearableHistory?: WearableData[];
    sleepHistory?: SleepLog[];
    riskHistory?: MentalStatusLog[];
}

function ChartsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {[1, 2].map((i) => (
                <Card key={i}>
                    <CardHeader>
                        <Skeleton className="h-5 w-48" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-[220px] w-full rounded-lg" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function StatsSkeleton() {
    return (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {[1, 2, 3, 4].map((i) => (
                <Card key={i}>
                    <CardHeader className="pb-2">
                        <Skeleton className="h-4 w-24" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-9 w-20" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function RiskHistorySkeleton() {
    return (
        <Card>
            <CardHeader>
                <Skeleton className="h-5 w-48" />
            </CardHeader>
            <CardContent className="space-y-3">
                {[1, 2, 3].map((i) => (
                    <Skeleton key={i} className="h-16 w-full rounded-lg" />
                ))}
            </CardContent>
        </Card>
    );
}

const riskColors: Record<string, string> = {
    low: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    critical: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

const riskLabel: Record<string, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

function StatCard({
    icon: Icon,
    label,
    value,
    unit,
    trend,
}: {
    icon: React.ElementType;
    label: string;
    value: string | number | null;
    unit?: string;
    trend?: 'up' | 'down' | null;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="flex items-end gap-2">
                    <p className="text-3xl font-bold">
                        {value != null ? (
                            value
                        ) : (
                            <span className="text-xl text-muted-foreground">
                                —
                            </span>
                        )}
                    </p>
                    {unit && value != null && (
                        <span className="mb-1 text-sm text-muted-foreground">
                            {unit}
                        </span>
                    )}
                    {trend === 'up' && (
                        <TrendingUp className="mb-1 size-4 text-emerald-500" />
                    )}
                    {trend === 'down' && (
                        <TrendingDown className="mb-1 size-4 text-red-500" />
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function DeferredContent({
    wearableHistory,
    sleepHistory,
    riskHistory,
}: {
    wearableHistory: WearableData[];
    sleepHistory: SleepLog[];
    riskHistory: MentalStatusLog[];
}) {
    const safeWearableHistory = Array.isArray(wearableHistory)
        ? wearableHistory
        : [];
    const safeSleepHistory = Array.isArray(sleepHistory) ? sleepHistory : [];
    const safeRiskHistory = Array.isArray(riskHistory) ? riskHistory : [];

    const latestWearable = safeWearableHistory[0];
    const latestSleep = safeSleepHistory[0];

    const hrvChartData = [...safeWearableHistory]
        .reverse()
        .slice(-24)
        .map((w) => ({
            time: new Date(w.recorded_at).toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            }),
            hrv: w.hrv_score != null ? Number(w.hrv_score).toFixed(1) : null,
            stress:
                w.stress_index != null
                    ? Number(w.stress_index).toFixed(1)
                    : null,
        }));

    const sleepChartData = [...safeSleepHistory]
        .reverse()
        .slice(-14)
        .map((s) => ({
            date: new Date(s.sleep_date).toLocaleDateString('id-ID', {
                month: 'short',
                day: 'numeric',
            }),
            duration: Math.round((s.duration_minutes / 60) * 10) / 10,
            quality: s.quality_score,
        }));

    return (
        <>
            {/* Stats Row */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    icon={Activity}
                    label="HRV Terakhir"
                    value={
                        latestWearable?.hrv_score != null
                            ? Number(latestWearable.hrv_score).toFixed(1)
                            : null
                    }
                    unit="ms"
                />
                <StatCard
                    icon={Heart}
                    label="Denyut Jantung"
                    value={latestWearable?.heart_rate ?? null}
                    unit="bpm"
                />
                <StatCard
                    icon={Zap}
                    label="Indeks Stres"
                    value={
                        latestWearable?.stress_index != null
                            ? Number(latestWearable.stress_index).toFixed(0)
                            : null
                    }
                    unit="/100"
                />
                <StatCard
                    icon={Moon}
                    label="Durasi Tidur"
                    value={
                        latestSleep
                            ? (latestSleep.duration_minutes / 60).toFixed(1)
                            : null
                    }
                    unit="jam"
                />
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="size-4" />
                            HRV & Stres (24 jam terakhir)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {hrvChartData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={220}>
                                <AreaChart data={hrvChartData}>
                                    <defs>
                                        <linearGradient
                                            id="hrvGrad"
                                            x1="0"
                                            y1="0"
                                            x2="0"
                                            y2="1"
                                        >
                                            <stop
                                                offset="5%"
                                                stopColor="#3b82f6"
                                                stopOpacity={0.2}
                                            />
                                            <stop
                                                offset="95%"
                                                stopColor="#3b82f6"
                                                stopOpacity={0}
                                            />
                                        </linearGradient>
                                        <linearGradient
                                            id="stressGrad"
                                            x1="0"
                                            y1="0"
                                            x2="0"
                                            y2="1"
                                        >
                                            <stop
                                                offset="5%"
                                                stopColor="#f97316"
                                                stopOpacity={0.2}
                                            />
                                            <stop
                                                offset="95%"
                                                stopColor="#f97316"
                                                stopOpacity={0}
                                            />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-border"
                                    />
                                    <XAxis
                                        dataKey="time"
                                        tick={{ fontSize: 11 }}
                                        interval="preserveStartEnd"
                                    />
                                    <YAxis tick={{ fontSize: 11 }} />
                                    <Tooltip />
                                    <Area
                                        type="monotone"
                                        dataKey="hrv"
                                        stroke="#3b82f6"
                                        fill="url(#hrvGrad)"
                                        name="HRV (ms)"
                                        strokeWidth={2}
                                    />
                                    <Area
                                        type="monotone"
                                        dataKey="stress"
                                        stroke="#f97316"
                                        fill="url(#stressGrad)"
                                        name="Stres"
                                        strokeWidth={2}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="flex h-[220px] items-center justify-center text-muted-foreground">
                                Belum ada data wearable
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Moon className="size-4" />
                            Pola Tidur (14 hari terakhir)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {sleepChartData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={220}>
                                <AreaChart data={sleepChartData}>
                                    <defs>
                                        <linearGradient
                                            id="sleepGrad"
                                            x1="0"
                                            y1="0"
                                            x2="0"
                                            y2="1"
                                        >
                                            <stop
                                                offset="5%"
                                                stopColor="#8b5cf6"
                                                stopOpacity={0.2}
                                            />
                                            <stop
                                                offset="95%"
                                                stopColor="#8b5cf6"
                                                stopOpacity={0}
                                            />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-border"
                                    />
                                    <XAxis
                                        dataKey="date"
                                        tick={{ fontSize: 11 }}
                                        interval="preserveStartEnd"
                                    />
                                    <YAxis tick={{ fontSize: 11 }} />
                                    <Tooltip />
                                    <Area
                                        type="monotone"
                                        dataKey="duration"
                                        stroke="#8b5cf6"
                                        fill="url(#sleepGrad)"
                                        name="Durasi (jam)"
                                        strokeWidth={2}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="flex h-[220px] items-center justify-center text-muted-foreground">
                                Belum ada data tidur
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Risk History */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Brain className="size-4" />
                        Riwayat Status Mental
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {safeRiskHistory.length > 0 ? (
                        <div className="space-y-3">
                            {safeRiskHistory.map((log) => (
                                <div
                                    key={log.uuid}
                                    className="flex items-start justify-between rounded-lg border p-3"
                                >
                                    <div className="flex items-start gap-3">
                                        <span
                                            className={`mt-0.5 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${riskColors[log.risk_level] ?? ''}`}
                                        >
                                            {riskLabel[log.risk_level] ??
                                                log.risk_level}
                                        </span>
                                        <div>
                                            <p className="text-sm font-medium">
                                                Skor:{' '}
                                                {Number(log.risk_score).toFixed(
                                                    1,
                                                )}
                                            </p>
                                            {log.detected_emotion && (
                                                <p className="text-xs text-muted-foreground">
                                                    Emosi:{' '}
                                                    {log.detected_emotion}
                                                </p>
                                            )}
                                            {log.summary_note && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {log.summary_note}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        {new Date(
                                            log.created_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="py-6 text-center text-muted-foreground">
                            Belum ada riwayat status mental
                        </p>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

export default function PatientShow({
    patient,
    wearableHistory,
    sleepHistory,
    riskHistory,
}: Props) {
    const latestRisk = patient.latest_mental_status;

    return (
        <>
            <Head title={`${patient.name} — HeaLink`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button asChild variant="ghost" size="icon">
                        <Link href={patientsIndex.url()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div className="flex items-center gap-3">
                        <div className="flex size-12 items-center justify-center rounded-full bg-muted">
                            <User className="size-6 text-muted-foreground" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                {patient.name}
                            </h1>
                            <p className="text-muted-foreground">
                                {patient.email}
                            </p>
                        </div>
                    </div>
                    {latestRisk && (
                        <span
                            className={`ml-2 inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ${riskColors[latestRisk.risk_level] ?? ''}`}
                        >
                            {riskLabel[latestRisk.risk_level] ??
                                latestRisk.risk_level}
                        </span>
                    )}
                    <div className="ml-auto flex gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={
                                    patient.uuid
                                        ? chatLog.url({ user: patient.uuid })
                                        : patientsIndex.url()
                                }
                            >
                                <MessageCircle className="mr-2 size-4" />
                                Chat Log
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Profile Info */}
                {patient.profile && (
                    <Card>
                        <CardContent className="flex flex-wrap gap-6 p-4">
                            {patient.profile.gender && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Jenis Kelamin
                                    </p>
                                    <p className="font-medium capitalize">
                                        {patient.profile.gender}
                                    </p>
                                </div>
                            )}
                            {patient.profile.dob && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Tanggal Lahir
                                    </p>
                                    <p className="font-medium">
                                        {new Date(
                                            patient.profile.dob,
                                        ).toLocaleDateString('id-ID')}
                                    </p>
                                </div>
                            )}
                            {patient.profile.job && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Pekerjaan
                                    </p>
                                    <p className="font-medium">
                                        {patient.profile.job}
                                    </p>
                                </div>
                            )}
                            {patient.profile.phone && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Telepon
                                    </p>
                                    <p className="font-medium">
                                        {patient.profile.phone}
                                    </p>
                                </div>
                            )}
                            {patient.latest_screening?.bmi != null && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        BMI
                                    </p>
                                    <p className="font-medium">
                                        {Number(
                                            patient.latest_screening.bmi,
                                        ).toFixed(1)}
                                    </p>
                                </div>
                            )}
                            {patient.latest_screening?.phq9_score != null && (
                                <div>
                                    <p className="text-xs text-muted-foreground">
                                        Skor PHQ-9
                                    </p>
                                    <p className="font-medium">
                                        {patient.latest_screening.phq9_score} /
                                        27
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Deferred: Stats + Charts + Risk History */}
                <Deferred
                    data={['wearableHistory', 'sleepHistory', 'riskHistory']}
                    fallback={
                        <>
                            <StatsSkeleton />
                            <ChartsSkeleton />
                            <RiskHistorySkeleton />
                        </>
                    }
                >
                    <DeferredContent
                        wearableHistory={wearableHistory ?? []}
                        sleepHistory={sleepHistory ?? []}
                        riskHistory={riskHistory ?? []}
                    />
                </Deferred>
            </div>
        </>
    );
}

PatientShow.layout = (props: { patient?: Patient }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Pasien', href: patientsIndex.url() },
        { title: props.patient?.name ?? 'Detail', href: '#' },
    ],
});
