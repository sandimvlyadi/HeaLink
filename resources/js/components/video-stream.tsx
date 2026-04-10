import { useAppearance } from '@/hooks/use-appearance';
import {
    CallControls,
    SpeakerLayout,
    StreamCall,
    StreamTheme,
    StreamVideo,
    StreamVideoClient,
    type User as StreamUser,
} from '@stream-io/video-react-sdk';
import '@stream-io/video-react-sdk/dist/css/styles.css';
import { useEffect, useRef, useState } from 'react';

interface Props {
    apiKey: string;
    token: string;
    userId: string;
    userName?: string;
    callId: string;
}

export default function VideoStream({
    apiKey,
    token,
    userId,
    userName,
    callId,
}: Props) {
    const { resolvedAppearance } = useAppearance();
    const [isJoined, setIsJoined] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const clientRef = useRef<StreamVideoClient | null>(null);
    const callRef = useRef<ReturnType<StreamVideoClient['call']> | null>(null);

    useEffect(() => {
        const user: StreamUser = { id: userId, name: userName ?? '-' };

        const client = new StreamVideoClient({ apiKey, user, token });
        clientRef.current = client;

        const call = client.call('default', callId);
        callRef.current = call;

        call.join({ create: true })
            .then(() => setIsJoined(true))
            .catch((err: unknown) => {
                setError(
                    err instanceof Error
                        ? err.message
                        : 'Gagal bergabung ke sesi video.',
                );
            });

        return () => {
            call.leave().catch(() => {});
            client.disconnectUser().catch(() => {});
        };
    }, [apiKey, token, userId, callId]);

    if (error) {
        return (
            <div className="flex min-h-[280px] items-center justify-center rounded-lg border-2 border-dashed border-destructive/40 bg-destructive/5 text-sm text-destructive">
                <p>{error}</p>
            </div>
        );
    }

    if (!isJoined || !clientRef.current || !callRef.current) {
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
        <StreamVideo client={clientRef.current}>
            <StreamCall call={callRef.current}>
                <StreamTheme className={resolvedAppearance}>
                    <SpeakerLayout />
                    <CallControls />
                </StreamTheme>
            </StreamCall>
        </StreamVideo>
    );
}
