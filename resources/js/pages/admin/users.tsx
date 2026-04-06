import { Head, Link } from '@inertiajs/react';
import { ShieldCheck, Users } from 'lucide-react';
import { index as adminUsersIndex } from '@/actions/App/Http/Controllers/Web/Admin/UserManagementController';
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
import type { PaginatedResource, UserRole } from '@/types';

interface User {
    uuid: string;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
    created_at: string;
}

interface Props {
    users: PaginatedResource<User>;
}

const roleColors: Record<UserRole, string> = {
    patient: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    medic: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
    admin: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
};

const roleLabel: Record<UserRole, string> = {
    patient: 'Pasien',
    medic: 'Dokter',
    admin: 'Admin',
};

export default function AdminUsers({ users }: Props) {
    const byRole = users.data.reduce<Record<string, number>>((acc, u) => {
        acc[u.role] = (acc[u.role] ?? 0) + 1;

        return acc;
    }, {});

    return (
        <>
            <Head title="Manajemen Pengguna — HeaLink Admin" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Manajemen Pengguna
                        </h1>
                        <p className="text-muted-foreground">
                            {users.meta.total} pengguna terdaftar
                        </p>
                    </div>
                    <ShieldCheck className="size-7 text-muted-foreground" />
                </div>

                {/* Role summary */}
                <div className="flex flex-wrap gap-3">
                    {(['patient', 'medic', 'admin'] as UserRole[]).map(
                        (role) => (
                            <div
                                key={role}
                                className={`flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium ${roleColors[role]}`}
                            >
                                <span>{roleLabel[role]}</span>
                                <span className="font-bold">
                                    {byRole[role] ?? 0}
                                </span>
                            </div>
                        ),
                    )}
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Peran</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Terdaftar</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Users className="size-8 opacity-30" />
                                                <span>Belum ada pengguna</span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    users.data.map((u) => (
                                        <TableRow key={u.uuid}>
                                            <TableCell className="font-medium">
                                                {u.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {u.email}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    className={`text-xs ${roleColors[u.role]}`}
                                                >
                                                    {roleLabel[u.role]}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <span
                                                    className={`inline-flex items-center gap-1.5 text-xs ${u.is_active ? 'text-emerald-600' : 'text-muted-foreground'}`}
                                                >
                                                    <span
                                                        className={`size-1.5 rounded-full ${u.is_active ? 'bg-emerald-500' : 'bg-muted-foreground'}`}
                                                    />
                                                    {u.is_active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {new Date(
                                                    u.created_at,
                                                ).toLocaleDateString('id-ID', {
                                                    day: 'numeric',
                                                    month: 'short',
                                                    year: 'numeric',
                                                })}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {users.meta.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Halaman {users.meta.current_page} dari{' '}
                            {users.meta.last_page}
                            {' · '}
                            {users.meta.total} total
                        </span>
                        <div className="flex gap-2">
                            {users.links.prev && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={users.links.prev}>
                                        Sebelumnya
                                    </Link>
                                </Button>
                            )}
                            {users.links.next && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={users.links.next}>
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

AdminUsers.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Admin', href: adminUsersIndex.url() },
        { title: 'Pengguna', href: adminUsersIndex.url() },
    ],
});
