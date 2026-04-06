import { Head, Link } from '@inertiajs/react';
import { Calendar, Clock, LogIn, Users } from 'lucide-react';
import {
    index as consultationsIndex,
    room,
} from '@/actions/App/Http/Controllers/Web/ConsultationController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    Patient,
} from '@/types';

interface Props {
    consultations: PaginatedResource<Consultation>;
    patients: Patient[];
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

export default function ConsultationsIndex({ consultations }: Props) {
    const pendingCount = consultations.data.filter(
        (c) => c.status === 'pending',
    ).length;
    const ongoingCount = consultations.data.filter(
        (c) => c.status === 'ongoing',
    ).length;
    const completedCount = consultations.data.filter(
        (c) => c.status === 'completed',
    ).length;

    return (
        <>
            <Head title="Konsultasi — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Konsultasi
                    </h1>
                    <p className="text-muted-foreground">
                        {consultations.meta.total} total konsultasi terdaftar
                    </p>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Menunggu
                            </CardTitle>
                            <Clock className="size-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-amber-600">
                                {pendingCount}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Berlangsung
                            </CardTitle>
                            <LogIn className="size-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-blue-600">
                                {ongoingCount}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Selesai
                            </CardTitle>
                            <Calendar className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-emerald-600">
                                {completedCount}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Pasien</TableHead>
                                    <TableHead>Dokter</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Dijadwalkan</TableHead>
                                    <TableHead>Dimulai</TableHead>
                                    <TableHead className="w-[100px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {consultations.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Users className="size-8 opacity-30" />
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
                                                    {c.patient?.name ?? '—'}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {c.patient?.email}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div>
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
                                            <TableCell className="text-sm">
                                                {c.started_at
                                                    ? new Date(
                                                          c.started_at,
                                                      ).toLocaleDateString(
                                                          'id-ID',
                                                          {
                                                              day: 'numeric',
                                                              month: 'short',
                                                              hour: '2-digit',
                                                              minute: '2-digit',
                                                          },
                                                      )
                                                    : '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Button asChild size="sm">
                                                    <Link
                                                        href={
                                                            room({
                                                                consultation:
                                                                    c.uuid,
                                                            }).url
                                                        }
                                                    >
                                                        <LogIn className="mr-1.5 size-3.5" />
                                                        Masuk
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {consultations.meta.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Halaman {consultations.meta.current_page} dari{' '}
                            {consultations.meta.last_page}
                            {' · '}
                            {consultations.meta.total} total
                        </span>
                        <div className="flex gap-2">
                            {consultations.links.prev && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={consultations.links.prev}>
                                        Sebelumnya
                                    </Link>
                                </Button>
                            )}
                            {consultations.links.next && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={consultations.links.next}>
                                        Berikutnya
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

ConsultationsIndex.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Konsultasi', href: consultationsIndex.url() },
    ],
});
