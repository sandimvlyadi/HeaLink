import {
    markAllRead,
    index as notificationsIndex,
} from '@/actions/App/Http/Controllers/Web/NotificationController';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { AppNotification, PaginatedResource } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Bell, BellOff, CheckCheck, Info, TriangleAlert } from 'lucide-react';

interface Props {
    notifications: PaginatedResource<AppNotification>;
    unread_count: number;
}

const typeIcon: Record<string, React.ElementType> = {
    info: Info,
    warning: TriangleAlert,
    critical: TriangleAlert,
    reminder: Bell,
};

const typeColors: Record<string, string> = {
    info: 'text-blue-600 dark:text-blue-400',
    warning: 'text-amber-600 dark:text-amber-400',
    critical: 'text-red-600 dark:text-red-400',
    reminder: 'text-purple-600 dark:text-purple-400',
};

const typeLabel: Record<string, string> = {
    info: 'Info',
    warning: 'Peringatan',
    critical: 'Kritis',
    reminder: 'Pengingat',
};

export default function NotificationsIndex({
    notifications,
    unread_count,
}: Props) {
    const handleMarkAllRead = () => {
        router.patch(markAllRead.url(), {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Notifikasi — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Notifikasi
                        </h1>
                        <p className="text-muted-foreground">
                            {unread_count > 0
                                ? `${unread_count} belum dibaca`
                                : 'Semua notifikasi telah dibaca'}
                        </p>
                    </div>
                    {unread_count > 0 && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleMarkAllRead}
                        >
                            <CheckCheck className="mr-2 size-4" />
                            Tandai Semua Dibaca
                        </Button>
                    )}
                </div>

                {/* Notifications List */}
                <Card>
                    <CardContent className="p-0">
                        {notifications.data.length > 0 ? (
                            <div className="divide-y">
                                {notifications.data.map((notif) => {
                                    const Icon = typeIcon[notif.type] ?? Bell;
                                    return (
                                        <div
                                            key={notif.uuid}
                                            className={`flex gap-4 p-4 transition-colors hover:bg-muted/50 ${!notif.is_read ? 'bg-muted/30' : ''}`}
                                        >
                                            <div
                                                className={`mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-muted ${typeColors[notif.type] ?? ''}`}
                                            >
                                                <Icon className="size-4" />
                                            </div>
                                            <div className="flex-1 space-y-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div>
                                                        <p
                                                            className={`text-sm font-medium ${!notif.is_read ? 'font-semibold' : ''}`}
                                                        >
                                                            {notif.title}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {notif.message}
                                                        </p>
                                                    </div>
                                                    <div className="flex shrink-0 items-center gap-2">
                                                        {!notif.is_read && (
                                                            <span className="size-2 rounded-full bg-blue-500" />
                                                        )}
                                                        <span className="text-xs text-muted-foreground">
                                                            {new Date(
                                                                notif.created_at,
                                                            ).toLocaleDateString(
                                                                'id-ID',
                                                                {
                                                                    day: 'numeric',
                                                                    month: 'short',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit',
                                                                },
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                                <span
                                                    className={`inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${typeColors[notif.type] ?? ''}`}
                                                >
                                                    {typeLabel[notif.type] ??
                                                        notif.type}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center gap-3 py-20 text-muted-foreground">
                                <BellOff className="size-12 opacity-30" />
                                <p>Tidak ada notifikasi</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {notifications.meta.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Halaman {notifications.meta.current_page} dari{' '}
                            {notifications.meta.last_page}
                        </span>
                        <div className="flex gap-2">
                            {notifications.links.prev && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={notifications.links.prev}>
                                        Sebelumnya
                                    </Link>
                                </Button>
                            )}
                            {notifications.links.next && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={notifications.links.next}>
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

NotificationsIndex.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Notifikasi', href: notificationsIndex.url() },
    ],
});
