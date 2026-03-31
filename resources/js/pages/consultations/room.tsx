import { index as consultationsIndex } from '@/actions/App/Http/Controllers/Web/ConsultationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import type { Consultation, ConsultationStatus } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Brain, Calendar, Clock, User } from 'lucide-react';

interface Props {
    consultation: Consultation;
}

const statusColors: Record<ConsultationStatus, string> = {
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    ongoing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    completed:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-muted text-muted-foreground',
};

const statusLabel: Record<ConsultationStatus, string> = {
    pending: 'Menunggu',
    ongoing: 'Berlangsung',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start gap-4 py-2">
            <span className="w-40 shrink-0 text-sm text-muted-foreground">
                {label}
            </span>
            <span className="text-sm font-medium">{value ?? '—'}</span>
        </div>
    );
}

export default function ConsultationRoom({ consultation }: Props) {
    const patient = consultation.patient;
    const medic = consultation.medic;
    const risk = patient?.latest_mental_status?.risk_level;

    const riskColors: Record<string, string> = {
        low: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        critical:
            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    const riskLabel: Record<string, string> = {
        low: 'Rendah',
        medium: 'Sedang',
        high: 'Tinggi',
        critical: 'Kritis',
    };

    return (
        <>
            <Head title={`Ruang Konsultasi — ${patient?.name ?? 'Pasien'}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button asChild variant="ghost" size="icon">
                        <Link href={consultationsIndex.url()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight">
                                Ruang Konsultasi
                            </h1>
                            <Badge
                                className={`text-xs ${statusColors[consultation.status]}`}
                            >
                                {statusLabel[consultation.status]}
                            </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {consultation.scheduled_at
                                ? new Date(
                                      consultation.scheduled_at,
                                  ).toLocaleDateString('id-ID', {
                                      weekday: 'long',
                                      day: 'numeric',
                                      month: 'long',
                                      year: 'numeric',
                                      hour: '2-digit',
                                      minute: '2-digit',
                                  })
                                : 'Belum dijadwalkan'}
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left column — patient + consultation info */}
                    <div className="flex flex-col gap-6 lg:col-span-1">
                        {/* Patient Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <User className="size-4" />
                                    Informasi Pasien
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-0">
                                <div className="flex items-center gap-3 pb-4">
                                    <div className="flex size-12 items-center justify-center rounded-full bg-muted text-lg font-bold">
                                        {patient?.name
                                            ?.charAt(0)
                                            .toUpperCase() ?? '?'}
                                    </div>
                                    <div>
                                        <p className="font-semibold">
                                            {patient?.name ?? '—'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {patient?.email}
                                        </p>
                                    </div>
                                </div>
                                <Separator />
                                <div className="space-y-1 pt-3">
                                    <InfoRow
                                        label="Jenis Kelamin"
                                        value={patient?.profile?.gender}
                                    />
                                    <InfoRow
                                        label="Pekerjaan"
                                        value={patient?.profile?.job}
                                    />
                                    <InfoRow
                                        label="Telepon"
                                        value={patient?.profile?.phone}
                                    />
                                    <InfoRow
                                        label="Risiko Mental"
                                        value={
                                            risk ? (
                                                <Badge
                                                    className={`text-xs ${riskColors[risk]}`}
                                                >
                                                    {riskLabel[risk]}
                                                </Badge>
                                            ) : (
                                                '—'
                                            )
                                        }
                                    />
                                    <InfoRow
                                        label="Skor Risiko"
                                        value={
                                            patient?.latest_mental_status
                                                ?.risk_score ?? '—'
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Medic Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Brain className="size-4" />
                                    Dokter / Terapis
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="pt-0">
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-full bg-muted text-sm font-bold">
                                        {medic?.name?.charAt(0).toUpperCase() ??
                                            '?'}
                                    </div>
                                    <div>
                                        <p className="font-medium">
                                            {medic?.name ?? '—'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {medic?.email}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Timestamps */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Clock className="size-4" />
                                    Waktu
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1 pt-0">
                                <InfoRow
                                    label="Dijadwalkan"
                                    value={
                                        consultation.scheduled_at
                                            ? new Date(
                                                  consultation.scheduled_at,
                                              ).toLocaleString('id-ID')
                                            : null
                                    }
                                />
                                <InfoRow
                                    label="Dimulai"
                                    value={
                                        consultation.started_at
                                            ? new Date(
                                                  consultation.started_at,
                                              ).toLocaleString('id-ID')
                                            : null
                                    }
                                />
                                <InfoRow
                                    label="Berakhir"
                                    value={
                                        consultation.ended_at
                                            ? new Date(
                                                  consultation.ended_at,
                                              ).toLocaleString('id-ID')
                                            : null
                                    }
                                />
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right column — video placeholder + notes */}
                    <div className="flex flex-col gap-6 lg:col-span-2">
                        {/* Video call placeholder */}
                        <Card className="flex-1">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Calendar className="size-4" />
                                    Sesi Video
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex min-h-[280px] items-center justify-center rounded-lg border-2 border-dashed border-muted bg-muted/20 text-sm text-muted-foreground">
                                    {consultation.status === 'ongoing' ? (
                                        <div className="space-y-2 text-center">
                                            <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                                <div className="size-4 animate-pulse rounded-full bg-blue-500" />
                                            </div>
                                            <p className="font-medium text-blue-600 dark:text-blue-400">
                                                Sesi Berlangsung
                                            </p>
                                            <p className="text-xs">
                                                Integrasi video call akan aktif
                                                di sini
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="space-y-1 text-center">
                                            <p>Sesi video belum dimulai</p>
                                            <p className="text-xs">
                                                Status:{' '}
                                                {
                                                    statusLabel[
                                                        consultation.status
                                                    ]
                                                }
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Notes */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Catatan Konsultasi
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {consultation.notes ? (
                                    <p className="text-sm leading-relaxed whitespace-pre-wrap">
                                        {consultation.notes}
                                    </p>
                                ) : (
                                    <Textarea
                                        disabled
                                        placeholder="Belum ada catatan untuk konsultasi ini"
                                        className="min-h-[100px] resize-none"
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

ConsultationRoom.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Konsultasi', href: consultationsIndex.url() },
        { title: 'Ruang Konsultasi' },
    ],
});
