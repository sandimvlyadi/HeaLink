import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, Stethoscope } from 'lucide-react';
import {
    cancel,
    index as consultationsIndex,
} from '@/actions/App/Http/Controllers/Web/Patient/ConsultationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { Consultation, ConsultationStatus } from '@/types';

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

function formatDate(iso: string | null) {
    if (!iso) {
return '—';
}

    return new Date(iso).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

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

export default function PatientConsultationShow({ consultation }: Props) {
    const medic = consultation.medic;

    function handleCancel() {
        if (!confirm('Yakin ingin membatalkan konsultasi ini?')) {
return;
}

        router.patch(cancel.url({ consultation: consultation.uuid }));
    }

    return (
        <>
            <Head title="Detail Konsultasi — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Button asChild variant="ghost" size="icon">
                        <Link href={consultationsIndex.url()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Detail Konsultasi
                        </h1>
                        <p className="text-muted-foreground">
                            ID: {consultation.uuid}
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Consultation Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Calendar className="size-4" />
                                Informasi Konsultasi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <InfoRow
                                label="Status"
                                value={
                                    <Badge
                                        className={`text-xs ${statusColors[consultation.status]}`}
                                    >
                                        {statusLabel[consultation.status]}
                                    </Badge>
                                }
                            />
                            <Separator />
                            <InfoRow
                                label="Dijadwalkan"
                                value={
                                    <span className="flex items-center gap-1">
                                        <Calendar className="size-3 text-muted-foreground" />
                                        {formatDate(consultation.scheduled_at)}
                                    </span>
                                }
                            />
                            <Separator />
                            <InfoRow
                                label="Dimulai"
                                value={
                                    consultation.started_at ? (
                                        <span className="flex items-center gap-1">
                                            <Clock className="size-3 text-muted-foreground" />
                                            {formatDate(
                                                consultation.started_at,
                                            )}
                                        </span>
                                    ) : null
                                }
                            />
                            <Separator />
                            <InfoRow
                                label="Selesai"
                                value={formatDate(consultation.ended_at)}
                            />
                            {consultation.notes && (
                                <>
                                    <Separator />
                                    <InfoRow
                                        label="Catatan Dokter"
                                        value={consultation.notes}
                                    />
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Medic Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Stethoscope className="size-4" />
                                Dokter
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <InfoRow label="Nama" value={medic?.name} />
                            <Separator />
                            <InfoRow label="Email" value={medic?.email} />
                            {medic?.profile?.job && (
                                <>
                                    <Separator />
                                    <InfoRow
                                        label="Spesialisasi"
                                        value={medic.profile.job}
                                    />
                                </>
                            )}
                            {medic?.profile?.phone && (
                                <>
                                    <Separator />
                                    <InfoRow
                                        label="Telepon"
                                        value={medic.profile.phone}
                                    />
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Actions */}
                {consultation.status === 'pending' && (
                    <div className="flex gap-3">
                        <Button variant="destructive" onClick={handleCancel}>
                            Batalkan Konsultasi
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}
