import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bot, Flag, User } from 'lucide-react';
import {
    show as patientShow,
    index as patientsIndex,
} from '@/actions/App/Http/Controllers/Web/PatientController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { ChatHistory, PaginatedResource, Patient } from '@/types';

interface Props {
    patient: Patient;
    chatHistories: PaginatedResource<ChatHistory>;
}

const sentimentColor = (score: number | null) => {
    if (score == null) {
        return 'text-muted-foreground';
    }

    if (score >= 0.3) {
        return 'text-emerald-600 dark:text-emerald-400';
    }

    if (score <= -0.3) {
        return 'text-red-600 dark:text-red-400';
    }

    return 'text-amber-600 dark:text-amber-400';
};

const sentimentLabel = (score: number | null) => {
    if (score == null) {
        return null;
    }

    if (score >= 0.3) {
        return 'Positif';
    }

    if (score <= -0.3) {
        return 'Negatif';
    }

    return 'Netral';
};

export default function PatientChatLog({ patient, chatHistories }: Props) {
    return (
        <>
            <Head title={`Chat Log — ${patient.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button asChild variant="ghost" size="icon">
                        <Link
                            href={
                                patient.uuid
                                    ? patientShow.url({ user: patient.uuid })
                                    : patientsIndex.url()
                            }
                        >
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Chat Log
                        </h1>
                        <p className="text-muted-foreground">
                            {patient.name} — {chatHistories.meta.total} pesan
                        </p>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    <Card>
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Total Pesan
                            </p>
                            <p className="text-2xl font-bold">
                                {chatHistories.meta.total}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Pesan Ditandai
                            </p>
                            <p className="text-2xl font-bold text-amber-600">
                                {
                                    chatHistories.data.filter(
                                        (c) => c.is_flagged,
                                    ).length
                                }
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Sentimen Negatif
                            </p>
                            <p className="text-2xl font-bold text-red-600">
                                {
                                    chatHistories.data.filter(
                                        (c) => (c.sentiment_score ?? 0) <= -0.3,
                                    ).length
                                }
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Chat Messages */}
                <Card className="flex-1">
                    <CardHeader>
                        <CardTitle>Riwayat Percakapan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ScrollArea className="h-[600px] pr-4">
                            <div className="space-y-4">
                                {chatHistories.data.length > 0 ? (
                                    chatHistories.data.map((msg) => (
                                        <div
                                            key={msg.uuid}
                                            className={`flex gap-3 ${msg.sender_type === 'ai' ? 'flex-row-reverse' : ''}`}
                                        >
                                            <div
                                                className={`flex size-8 shrink-0 items-center justify-center rounded-full ${msg.sender_type === 'ai' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-muted'}`}
                                            >
                                                {msg.sender_type === 'ai' ? (
                                                    <Bot className="size-4 text-blue-600 dark:text-blue-400" />
                                                ) : (
                                                    <User className="size-4 text-muted-foreground" />
                                                )}
                                            </div>

                                            <div
                                                className={`max-w-[70%] space-y-1 ${msg.sender_type === 'ai' ? 'items-end' : ''}`}
                                            >
                                                <div
                                                    className={`rounded-2xl px-4 py-2 text-sm ${
                                                        msg.sender_type === 'ai'
                                                            ? 'bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-100'
                                                            : msg.is_flagged
                                                              ? 'bg-amber-50 text-amber-900 ring-1 ring-amber-300 dark:bg-amber-900/20 dark:text-amber-100'
                                                              : 'bg-muted'
                                                    }`}
                                                >
                                                    {msg.is_flagged && (
                                                        <div className="mb-1 flex items-center gap-1 text-xs text-amber-600">
                                                            <Flag className="size-3" />
                                                            <span>
                                                                Ditandai untuk
                                                                review
                                                            </span>
                                                        </div>
                                                    )}
                                                    <p>{msg.message}</p>
                                                </div>

                                                <div
                                                    className={`flex items-center gap-2 px-1 text-xs text-muted-foreground ${msg.sender_type === 'ai' ? 'flex-row-reverse' : ''}`}
                                                >
                                                    <span>
                                                        {new Date(
                                                            msg.created_at,
                                                        ).toLocaleTimeString(
                                                            'id-ID',
                                                            {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            },
                                                        )}{' '}
                                                        {new Date(
                                                            msg.created_at,
                                                        ).toLocaleDateString(
                                                            'id-ID',
                                                            {
                                                                day: 'numeric',
                                                                month: 'short',
                                                            },
                                                        )}
                                                    </span>
                                                    {msg.sentiment_score !=
                                                        null &&
                                                        msg.sender_type ===
                                                            'user' && (
                                                            <span
                                                                className={sentimentColor(
                                                                    msg.sentiment_score,
                                                                )}
                                                            >
                                                                {sentimentLabel(
                                                                    msg.sentiment_score,
                                                                )}{' '}
                                                                (
                                                                {msg.sentiment_score >
                                                                0
                                                                    ? '+'
                                                                    : ''}
                                                                {Number(
                                                                    msg.sentiment_score,
                                                                ).toFixed(2)}
                                                                )
                                                            </span>
                                                        )}
                                                    {msg.detected_emotion &&
                                                        msg.sender_type ===
                                                            'user' && (
                                                            <span className="text-muted-foreground capitalize">
                                                                {
                                                                    msg.detected_emotion
                                                                }
                                                            </span>
                                                        )}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="py-16 text-center text-muted-foreground">
                                        Belum ada riwayat percakapan
                                    </div>
                                )}
                            </div>
                        </ScrollArea>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {chatHistories.meta.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Halaman {chatHistories.meta.current_page} dari{' '}
                            {chatHistories.meta.last_page}
                        </span>
                        <div className="flex gap-2">
                            {chatHistories.links.prev && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={chatHistories.links.prev}>
                                        Sebelumnya
                                    </Link>
                                </Button>
                            )}
                            {chatHistories.links.next && (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={chatHistories.links.next}>
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

PatientChatLog.layout = (props: { patient?: Patient }) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Pasien', href: patientsIndex.url() },
        {
            title: props.patient?.name ?? 'Pasien',
            href: props.patient?.uuid
                ? patientShow.url({ user: props.patient.uuid })
                : '#',
        },
        { title: 'Chat Log', href: '#' },
    ],
});
