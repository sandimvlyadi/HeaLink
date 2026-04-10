import { useAppearance } from '@/hooks/use-appearance';
import type { User as StreamUser } from '@stream-io/video-react-sdk';
import {
    CallControls,
    SpeakerLayout,
    StreamCall,
    StreamTheme,
    StreamVideo,
    StreamVideoClient,
} from '@stream-io/video-react-sdk';
import '@stream-io/video-react-sdk/dist/css/styles.css';
import { useEffect, useState } from 'react';

interface Props {
    apiKey: string;
    token: string;
    userId: string;
    userName?: string;
    callId: string;
}

type StreamSession = {
    client: StreamVideoClient;
    call: ReturnType<StreamVideoClient['call']>;
};

export default function VideoStream({
    apiKey,
    token,
    userId,
    userName,
    callId,
}: Props) {
    const { resolvedAppearance } = useAppearance();
    const [session, setSession] = useState<StreamSession | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const user: StreamUser = { id: userId, name: userName ?? '-' };
        const newClient = new StreamVideoClient({ apiKey, user, token });
        const newCall = newClient.call('default', callId);

        newCall
            .join({ create: true })
            .then(() => setSession({ client: newClient, call: newCall }))
            .catch((err: unknown) => {
                setError(
                    err instanceof Error
                        ? err.message
                        : 'Gagal bergabung ke sesi video.',
                );
            });

        return () => {
            newCall.leave().catch(() => {});
            newClient.disconnectUser().catch(() => {});
            setSession(null);
        };
    }, [apiKey, token, userId, callId, userName]);

    if (error) {
        return (
            <div className="flex min-h-[280px] items-center justify-center rounded-lg border-2 border-dashed border-destructive/40 bg-destructive/5 text-sm text-destructive">
                <p>{error}</p>
            </div>
        );
    }

    if (!session) {
        return (
            <div className="flex min-h-[280px] items-center justify-center rounded-lg border-2 border-dashed border-muted bg-muted/20">
                <div className="space-y-2 text-center text-sm text-muted-foreground">
                    <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <div className="size-4 animate-pulse rounded-full bg-blue-500" />
                    </div>
                    <p className="font-medium text-blue-600 dark:text-blue-400">
                        Menghubungkan...
                    </p>
                </div>
            </div>
        );
    }

    return (
        <StreamVideo client={session.client}>
            <StreamCall call={session.call}>
                <StreamTheme className={resolvedAppearance}>
                    <SpeakerLayout />
                    <CallControls />
                </StreamTheme>
            </StreamCall>
        </StreamVideo>
    );
}
