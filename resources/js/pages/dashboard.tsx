import { index as consultationsIndex } from '@/actions/App/Http/Controllers/Web/ConsultationController';
import {
    create as patientConsultationCreate,
    index as patientConsultationsIndex,
} from '@/actions/App/Http/Controllers/Web/Patient/ConsultationController';
import { show as patientShow } from '@/actions/App/Http/Controllers/Web/PatientController';
import { index as riskIndex } from '@/actions/App/Http/Controllers/Web/RiskController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDoctorRiskAlerts } from '@/hooks/use-real-time';
import { dashboard } from '@/routes';
import type {
    Consultation,
    ConsultationStatus,
    PaginatedResource,
    Patient,
} from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    CalendarClock,
    CalendarPlus,
    CheckCircle2,
    Clock,
    TrendingUp,
    Users,
} from 'lucide-react';

interface DashboardStats {
    total_patients: number;
    high_risk_patients: number;
    critical_patients: number;
    pending_consultations: number;
}

interface PatientStats {
    pending_consultations: number;
    total_consultations: number;
    completed_consultations: number;
}

interface Props {
    patients?: PaginatedResource<Patient>;
    stats?: DashboardStats;
    patientStats?: PatientStats;
    recentConsultations?: { data: Consultation[] };
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

const consultationStatusColors: Record<ConsultationStatus, string> = {
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    ongoing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    completed:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-muted text-muted-foreground',
};

const consultationStatusLabel: Record<ConsultationStatus, string> = {
    pending: 'Menunggu',
    ongoing: 'Berlangsung',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

export default function Dashboard({
    patients,
    stats,
    patientStats,
    recentConsultations,
}: Props) {
    const page = usePage();
    const dashboardUrl = page.props.currentTeam ? dashboard() : '/';
    const authUser = page.props.auth?.user as {
        role?: string;
        uuid?: string;
    } | null;

    const isMedic = authUser?.role === 'medic' || authUser?.role === 'admin';

    // Auto-refresh dashboard when a risk alert fires on the doctor's channel
    useDoctorRiskAlerts(isMedic ? (authUser?.uuid ?? null) : null, () => {
        router.reload({ only: ['patients', 'stats'] });
    });

    return (
        <>
            <Head title="Dashboard — HeaLink" />

            {isMedic && stats ? (
                <div className="flex flex-1 flex-col gap-6 p-6">
                    {/* Stat Cards */}
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Total Pasien
                                </CardTitle>
                                <Users className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold">
                                    {stats.total_patients}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Pasien aktif terdaftar
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Risiko Tinggi
                                </CardTitle>
                                <TrendingUp className="size-4 text-orange-500" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold text-orange-600">
                                    {stats.high_risk_patients}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    24 jam terakhir
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Kritis
                                </CardTitle>
                                <AlertTriangle className="size-4 text-red-500" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold text-red-600">
                                    {stats.critical_patients}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Perlu perhatian segera
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Konsultasi Pending
                                </CardTitle>
                                <CalendarClock className="size-4 text-blue-500" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold text-blue-600">
                                    {stats.pending_consultations}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Menunggu konfirmasi
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Quick Actions */}
                    <div className="flex gap-3">
                        <Button asChild variant="default" size="sm">
                            <Link href={riskIndex.url()}>
                                <Activity className="mr-2 size-4" />
                                Monitor Risiko
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={consultationsIndex.url()}>
                                <CalendarClock className="mr-2 size-4" />
                                Jadwal Konsultasi
                            </Link>
                        </Button>
                    </div>

                    {/* Patient Table */}
                    <Card className="flex-1">
                        <CardHeader>
                            <CardTitle>Pasien Terbaru</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Risiko Mental</TableHead>
                                        <TableHead>HRV Terakhir</TableHead>
                                        <TableHead>Stres</TableHead>
                                        <TableHead className="text-right"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {patients?.data.map((patient) => (
                                        <TableRow key={patient.uuid}>
                                            <TableCell className="font-medium">
                                                {patient.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {patient.email}
                                            </TableCell>
                                            <TableCell>
                                                {patient.latest_mental_status ? (
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${riskColors[patient.latest_mental_status.risk_level] ?? ''}`}
                                                    >
                                                        {riskLabel[
                                                            patient
                                                                .latest_mental_status
                                                                .risk_level
                                                        ] ??
                                                            patient
                                                                .latest_mental_status
                                                                .risk_level}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {patient.latest_wearable
                                                    ?.hrv_score != null ? (
                                                    <span className="font-mono text-sm">
                                                        {Number(
                                                            patient
                                                                .latest_wearable
                                                                .hrv_score,
                                                        ).toFixed(1)}{' '}
                                                        ms
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {patient.latest_wearable
                                                    ?.stress_index != null ? (
                                                    <span className="font-mono text-sm">
                                                        {Number(
                                                            patient
                                                                .latest_wearable
                                                                .stress_index,
                                                        ).toFixed(0)}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={patientShow.url({
                                                            user: patient.uuid,
                                                        })}
                                                    >
                                                        Detail
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {(!patients?.data ||
                                        patients.data.length === 0) && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="py-10 text-center text-muted-foreground"
                                            >
                                                Belum ada data pasien
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            ) : patientStats ? (
                <div className="flex flex-1 flex-col gap-6 p-6">
                    {/* Patient Stat Cards */}
                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Total Konsultasi
                                </CardTitle>
                                <CalendarClock className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold">
                                    {patientStats.total_consultations}
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Menunggu
                                </CardTitle>
                                <Clock className="size-4 text-amber-500" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold text-amber-600">
                                    {patientStats.pending_consultations}
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Selesai
                                </CardTitle>
                                <CheckCircle2 className="size-4 text-emerald-500" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-bold text-emerald-600">
                                    {patientStats.completed_consultations}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Quick Action */}
                    <div className="flex gap-3">
                        <Button asChild variant="default" size="sm">
                            <Link href={patientConsultationCreate.url()}>
                                <CalendarPlus className="mr-2 size-4" />
                                Buat Konsultasi
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={patientConsultationsIndex.url()}>
                                <CalendarClock className="mr-2 size-4" />
                                Semua Konsultasi
                            </Link>
                        </Button>
                    </div>

                    {/* Recent Consultations */}
                    <Card className="flex-1">
                        <CardHeader>
                            <CardTitle>Konsultasi Terbaru</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Dokter</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Dijadwalkan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentConsultations?.data.map((c) => (
                                        <TableRow key={c.uuid}>
                                            <TableCell className="font-medium">
                                                {c.medic?.name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${consultationStatusColors[c.status] ?? ''}`}
                                                >
                                                    {consultationStatusLabel[
                                                        c.status
                                                    ] ?? c.status}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {c.scheduled_at
                                                    ? new Date(
                                                          c.scheduled_at,
                                                      ).toLocaleDateString(
                                                          'id-ID',
                                                          {
                                                              day: 'numeric',
                                                              month: 'short',
                                                              year: 'numeric',
                                                              hour: '2-digit',
                                                              minute: '2-digit',
                                                          },
                                                      )
                                                    : '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {(!recentConsultations?.data ||
                                        recentConsultations.data.length ===
                                            0) && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="py-10 text-center text-muted-foreground"
                                            >
                                                Belum ada konsultasi
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            ) : (
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-6">
                    <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                        <Skeleton className="aspect-video rounded-xl" />
                        <Skeleton className="aspect-video rounded-xl" />
                        <Skeleton className="aspect-video rounded-xl" />
                    </div>
                    <Skeleton className="min-h-[60vh] flex-1 rounded-xl" />
                </div>
            )}
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard() : '/',
        },
    ],
});
