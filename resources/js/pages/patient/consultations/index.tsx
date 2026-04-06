import { Head, Link, router } from '@inertiajs/react';
import { Calendar, CalendarPlus, Clock, Stethoscope } from 'lucide-react';
import {
    cancel,
    create as createConsultation,
    show,
} from '@/actions/App/Http/Controllers/Web/Patient/ConsultationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type {
    Consultation,
    ConsultationStatus,
    PaginatedResource,
} from '@/types';

interface Props {
    consultations: PaginatedResource<Consultation>;
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
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function PatientConsultationsIndex({ consultations }: Props) {
    function handleCancel(uuid: string) {
        if (!confirm('Yakin ingin membatalkan konsultasi ini?')) {
return;
}

        router.patch(cancel.url({ consultation: uuid }));
    }

    return (
        <>
            <Head title="Konsultasi Saya — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Konsultasi Saya
                        </h1>
                        <p className="text-muted-foreground">
                            {consultations.meta.total} total konsultasi
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={createConsultation.url()}>
                            <CalendarPlus className="mr-2 size-4" />
                            Buat Konsultasi
                        </Link>
                    </Button>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Dokter</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Dijadwalkan</TableHead>
                                    <TableHead>Dimulai</TableHead>
                                    <TableHead className="w-[180px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {consultations.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Stethoscope className="size-8 opacity-30" />
                                                <span>
                                                    Belum ada konsultasi
                                                </span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    consultations.data.map((c) => (
                                        <TableRow key={c.uuid}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {c.medic?.name ?? '—'}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {c.medic?.email}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    className={`text-xs ${statusColors[c.status]}`}
                                                >
                                                    {statusLabel[c.status]}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                <div className="flex items-center gap-1">
                                                    <Calendar className="size-3 text-muted-foreground" />
                                                    {formatDate(c.scheduled_at)}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {c.started_at ? (
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="size-3 text-muted-foreground" />
                                                        {formatDate(
                                                            c.started_at,
                                                        )}
                                                    </div>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={show.url({
                                                                consultation:
                                                                    c.uuid,
                                                            })}
                                                        >
                                                            Detail
                                                        </Link>
                                                    </Button>
                                                    {c.status === 'pending' && (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleCancel(
                                                                    c.uuid,
                                                                )
                                                            }
                                                        >
                                                            Batalkan
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
