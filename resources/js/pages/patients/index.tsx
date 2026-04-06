import { Head, Link } from '@inertiajs/react';
import { MessageCircle, Search, User } from 'lucide-react';
import { useState } from 'react';
import {
    chatLog,
    show as patientShow,
    index as patientsIndex,
} from '@/actions/App/Http/Controllers/Web/PatientController';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { PaginatedResource, Patient } from '@/types';

interface Props {
    patients: PaginatedResource<Patient>;
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

export default function PatientsIndex({ patients }: Props) {
    const [search, setSearch] = useState('');

    const filtered = patients.data.filter(
        (p) =>
            p.name.toLowerCase().includes(search.toLowerCase()) ||
            p.email.toLowerCase().includes(search.toLowerCase()),
    );

    return (
        <>
            <Head title="Pasien — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Daftar Pasien
                        </h1>
                        <p className="text-muted-foreground">
                            {patients.meta.total} pasien terdaftar
                        </p>
                    </div>
                </div>

                {/* Search */}
                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Cari nama atau email..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-9"
                    />
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Status Risiko</TableHead>
                                    <TableHead>Skor Risiko</TableHead>
                                    <TableHead>HRV</TableHead>
                                    <TableHead>Denyut Jantung</TableHead>
                                    <TableHead className="text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filtered.map((patient) => (
                                    <TableRow key={patient.uuid}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-8 items-center justify-center rounded-full bg-muted">
                                                    <User className="size-4 text-muted-foreground" />
                                                </div>
                                                <span className="font-medium">
                                                    {patient.name}
                                                </span>
                                            </div>
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
                                                    Belum ada data
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {patient.latest_mental_status
                                                ?.risk_score != null ? (
                                                <span className="font-mono text-sm">
                                                    {Number(
                                                        patient
                                                            .latest_mental_status
                                                            .risk_score,
                                                    ).toFixed(1)}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {patient.latest_wearable
                                                ?.hrv_score != null ? (
                                                <span className="font-mono text-sm">
                                                    {Number(
                                                        patient.latest_wearable
                                                            .hrv_score,
                                                    ).toFixed(1)}{' '}
                                                    ms
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {patient.latest_wearable
                                                ?.heart_rate != null ? (
                                                <span className="font-mono text-sm">
                                                    {
                                                        patient.latest_wearable
                                                            .heart_rate
                                                    }{' '}
                                                    bpm
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
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
                                                        Analitik
                                                    </Link>
                                                </Button>
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={chatLog.url({
                                                            user: patient.uuid,
                                                        })}
                                                    >
                                                        <MessageCircle className="size-4" />
                                                    </Link>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {filtered.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-12 text-center text-muted-foreground"
                                        >
                                            {search
                                                ? 'Tidak ada pasien yang cocok'
                                                : 'Belum ada data pasien'}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {patients.meta.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Menampilkan {patients.meta.from ?? 0}–
                            {patients.meta.to ?? 0} dari {patients.meta.total}
                        </span>
                        <div className="flex gap-2">
                            {patients.links.prev && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={patients.links.prev}>
                                        Sebelumnya
                                    </Link>
                                </Button>
                            )}
                            {patients.links.next && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={patients.links.next}>
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

PatientsIndex.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Pasien', href: patientsIndex.url() },
    ],
});
