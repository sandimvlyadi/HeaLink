import {
    cancel as consultationCancel,
    complete as consultationComplete,
    index as consultationsIndex,
    start as consultationStart,
} from '@/actions/App/Http/Controllers/Web/ConsultationController';
import ConsultationAnalysis from '@/components/consultation-analysis';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import VideoStream from '@/components/video-stream';
import type { Consultation, ConsultationStatus } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Brain,
    Calendar,
    CheckCircle,
    ChevronDown,
    Clock,
    PlayCircle,
    User,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

interface Props {
    consultation: Consultation;
    stream_api_key: string;
    stream_token: string;
    stream_user_id: string;
    stream_user_name: string;
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

export default function ConsultationRoom({
    consultation,
    stream_api_key,
    stream_token,
    stream_user_id,
    stream_user_name,
}: Props) {
    const page = usePage();
    const authUser = page.props.auth?.user as {
        uuid?: string;
        role?: string;
    };
    const isAdmin = authUser?.role === 'admin';
    const isAssignedMedic =
        authUser?.role === 'medic' &&
        authUser?.uuid === consultation.medic?.uuid;
    const canManageStatus = isAdmin || isAssignedMedic;

    const [processing, setProcessing] = useState(false);

    function handleStart() {
        setProcessing(true);
        router.patch(
            consultationStart({ consultation: consultation.uuid }).url,
            {},
            { onFinish: () => setProcessing(false) },
        );
    }

    function handleComplete() {
        setProcessing(true);
        router.patch(
            consultationComplete({ consultation: consultation.uuid }).url,
            {},
            { onFinish: () => setProcessing(false) },
        );
    }

    function handleCancel() {
        setProcessing(true);
        router.patch(
            consultationCancel({ consultation: consultation.uuid }).url,
            {},
            { onFinish: () => setProcessing(false) },
        );
    }

    const patient = consultation.patient;
    const medic = consultation.medic;
    const risk = patient?.latest_mental_status?.risk_level;

    const defaultOpen = consultation.status !== 'ongoing';
    const [patientOpen, setPatientOpen] = useState(defaultOpen);
    const [medicOpen, setMedicOpen] = useState(defaultOpen);
    const [timestampsOpen, setTimestampsOpen] = useState(defaultOpen);

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
                    {canManageStatus && (
                        <div className="flex items-center gap-2">
                            {consultation.status === 'pending' && (
                                <Button
                                    size="sm"
                                    onClick={handleStart}
                                    disabled={processing}
                                    className="gap-1.5"
                                >
                                    <PlayCircle className="size-4" />
                                    Mulai
                                </Button>
                            )}
                            {consultation.status === 'ongoing' && (
                                <Button
                                    size="sm"
                                    onClick={handleComplete}
                                    disabled={processing}
                                    className="gap-1.5"
                                >
                                    <CheckCircle className="size-4" />
                                    Selesaikan
                                </Button>
                            )}
                            {(consultation.status === 'pending' ||
                                consultation.status === 'ongoing') && (
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={handleCancel}
                                    disabled={processing}
                                    className="gap-1.5"
                                >
                                    <XCircle className="size-4" />
                                    Batalkan
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left column — patient + consultation info */}
                    <div className="flex flex-col gap-6 lg:col-span-1">
                        {/* Patient Info */}
                        <Collapsible
                            open={patientOpen}
                            onOpenChange={setPatientOpen}
                        >
                            <Card>
                                <CardHeader>
                                    <CollapsibleTrigger asChild>
                                        <button className="flex w-full items-center gap-2 text-left">
                                            <CardTitle className="flex flex-1 items-center gap-2 text-base">
                                                <User className="size-4" />
                                                Informasi Pasien
                                            </CardTitle>
                                            <ChevronDown
                                                className={`size-4 shrink-0 text-muted-foreground transition-transform duration-200 ${patientOpen ? 'rotate-180' : ''}`}
                                            />
                                        </button>
                                    </CollapsibleTrigger>
                                </CardHeader>
                                <CollapsibleContent>
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
                                                    patient
                                                        ?.latest_mental_status
                                                        ?.risk_score ?? '—'
                                                }
                                            />
                                        </div>
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Medic Info */}
                        <Collapsible
                            open={medicOpen}
                            onOpenChange={setMedicOpen}
                        >
                            <Card>
                                <CardHeader>
                                    <CollapsibleTrigger asChild>
                                        <button className="flex w-full items-center gap-2 text-left">
                                            <CardTitle className="flex flex-1 items-center gap-2 text-base">
                                                <Brain className="size-4" />
                                                Dokter / Terapis
                                            </CardTitle>
                                            <ChevronDown
                                                className={`size-4 shrink-0 text-muted-foreground transition-transform duration-200 ${medicOpen ? 'rotate-180' : ''}`}
                                            />
                                        </button>
                                    </CollapsibleTrigger>
                                </CardHeader>
                                <CollapsibleContent>
                                    <CardContent className="pt-0">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded-full bg-muted text-sm font-bold">
                                                {medic?.name
                                                    ?.charAt(0)
                                                    .toUpperCase() ?? '?'}
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
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Timestamps */}
                        <Collapsible
                            open={timestampsOpen}
                            onOpenChange={setTimestampsOpen}
                        >
                            <Card>
                                <CardHeader>
                                    <CollapsibleTrigger asChild>
                                        <button className="flex w-full items-center gap-2 text-left">
                                            <CardTitle className="flex flex-1 items-center gap-2 text-base">
                                                <Clock className="size-4" />
                                                Waktu
                                            </CardTitle>
                                            <ChevronDown
                                                className={`size-4 shrink-0 text-muted-foreground transition-transform duration-200 ${timestampsOpen ? 'rotate-180' : ''}`}
                                            />
                                        </button>
                                    </CollapsibleTrigger>
                                </CardHeader>
                                <CollapsibleContent>
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
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Emotion Analysis */}
                        <ConsultationAnalysis status={consultation.status} />
                    </div>

                    {/* Right column — video placeholder + notes */}
                    <div className="flex flex-col gap-6 lg:col-span-2">
                        {/* Video call placeholder */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Calendar className="size-4" />
                                    Sesi Video
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {consultation.status === 'ongoing' ? (
                                    <div className="overflow-hidden rounded-lg">
                                        <VideoStream
                                            apiKey={stream_api_key}
                                            token={stream_token}
                                            userId={stream_user_id}
                                            userName={stream_user_name}
                                            callId={consultation.uuid}
                                        />
                                    </div>
                                ) : (
                                    <div className="flex min-h-[280px] items-center justify-center rounded-lg border-2 border-dashed border-muted bg-muted/20 text-sm text-muted-foreground">
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
                                    </div>
                                )}
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
                                        disabled={
                                            consultation.status !== 'ongoing'
                                        }
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
