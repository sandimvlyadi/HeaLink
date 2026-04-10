import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { ConsultationStatus } from '@/types';
import { Activity, Brain, Camera, Mic } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type Emotion = 'senang' | 'sedih' | 'netral' | 'cemas' | 'marah' | 'takut';

interface EmotionScore {
    emotion: Emotion;
    score: number;
}

interface VideoCapture {
    id: number;
    time: string;
    dominant: Emotion;
    scores: EmotionScore[];
}

interface VoiceSegment {
    id: number;
    time: string;
    dominant: Emotion;
    tone: string;
    scores: EmotionScore[];
}

interface TranscriptEntry {
    id: number;
    time: string;
    text: string;
    dominant: Emotion;
    notes: string;
}

const EMOTION_COLORS: Record<Emotion, string> = {
    senang: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    netral: 'bg-slate-100 text-slate-600 dark:bg-slate-800/50 dark:text-slate-400',
    sedih: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    cemas: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    marah: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    takut: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
};

const EMOTION_BAR_COLORS: Record<Emotion, string> = {
    senang: 'bg-emerald-500',
    netral: 'bg-slate-400',
    sedih: 'bg-indigo-500',
    cemas: 'bg-amber-500',
    marah: 'bg-red-500',
    takut: 'bg-orange-500',
};

const EMOTION_LABELS: Record<Emotion, string> = {
    senang: 'Senang',
    sedih: 'Sedih',
    netral: 'Netral',
    cemas: 'Cemas',
    marah: 'Marah',
    takut: 'Takut',
};

const ALL_EMOTIONS: Emotion[] = [
    'senang',
    'sedih',
    'netral',
    'cemas',
    'marah',
    'takut',
];
const TONES = ['Tenang', 'Tegang', 'Bergetar', 'Datar', 'Berenergi', 'Lesu'];
const TRANSCRIPT_POOL = [
    {
        text: '"Saya merasa sedikit lebih baik hari ini, tapi masih ada kekhawatiran yang mengganjal..."',
        notes: 'Menunjukkan ambivalensi emosional. Kemajuan kecil terdeteksi namun kecemasan latar belakang masih hadir.',
    },
    {
        text: '"Situasi di rumah memang membuat saya lebih tertekan belakangan ini."',
        notes: 'Faktor stres eksternal dari lingkungan domestik. Perlu eksplorasi lebih dalam mengenai dinamika keluarga.',
    },
    {
        text: '"Saya tidak tahu harus mulai dari mana untuk memperbaiki semuanya."',
        notes: 'Perasaan kewalahan (overwhelmed) dan ketidakberdayaan terdeteksi. Intervensi kognitif mungkin diperlukan.',
    },
    {
        text: '"Ada momen-momen ketika saya merasa lebih ringan, tapi tidak bertahan lama."',
        notes: 'Fluktuasi mood terdeteksi. Faktor pemicu perlu diidentifikasi untuk pola manajemen yang lebih baik.',
    },
    {
        text: '"Saya mencoba melakukan apa yang Anda sarankan, tapi susah sekali konsisten."',
        notes: 'Hambatan dalam kepatuhan terapi teridentifikasi. Pendekatan yang lebih terstruktur mungkin membantu.',
    },
];

function randomScores(): EmotionScore[] {
    const raw = ALL_EMOTIONS.map((e) => ({ emotion: e, score: Math.random() }));
    const total = raw.reduce((acc, r) => acc + r.score, 0);

    return raw
        .map((r) => ({
            emotion: r.emotion,
            score: Math.round((r.score / total) * 100),
        }))
        .sort((a, b) => b.score - a.score);
}

function topEmotion(scores: EmotionScore[]): Emotion {
    return scores[0].emotion;
}

function formatNow(): string {
    return new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function overallDominant(items: Array<{ dominant: Emotion }>): Emotion | null {
    if (!items.length) {
        return null;
    }

    const counts: Partial<Record<Emotion, number>> = {};

    for (const item of items) {
        counts[item.dominant] = (counts[item.dominant] ?? 0) + 1;
    }

    return Object.entries(counts).sort(
        (a, b) => (b[1] ?? 0) - (a[1] ?? 0),
    )[0][0] as Emotion;
}

// ─── Static summary data for completed sessions ──────────────────────────────

const STATIC_CAPTURES: VideoCapture[] = [
    {
        id: 1,
        time: '09:00:05',
        dominant: 'netral',
        scores: [
            { emotion: 'netral', score: 43 },
            { emotion: 'cemas', score: 27 },
            { emotion: 'sedih', score: 15 },
            { emotion: 'senang', score: 8 },
            { emotion: 'takut', score: 5 },
            { emotion: 'marah', score: 2 },
        ],
    },
    {
        id: 2,
        time: '09:00:10',
        dominant: 'cemas',
        scores: [
            { emotion: 'cemas', score: 41 },
            { emotion: 'netral', score: 29 },
            { emotion: 'sedih', score: 17 },
            { emotion: 'senang', score: 7 },
            { emotion: 'takut', score: 4 },
            { emotion: 'marah', score: 2 },
        ],
    },
    {
        id: 3,
        time: '09:00:18',
        dominant: 'sedih',
        scores: [
            { emotion: 'sedih', score: 36 },
            { emotion: 'cemas', score: 31 },
            { emotion: 'netral', score: 20 },
            { emotion: 'senang', score: 7 },
            { emotion: 'takut', score: 4 },
            { emotion: 'marah', score: 2 },
        ],
    },
    {
        id: 4,
        time: '09:00:26',
        dominant: 'cemas',
        scores: [
            { emotion: 'cemas', score: 38 },
            { emotion: 'sedih', score: 25 },
            { emotion: 'netral', score: 22 },
            { emotion: 'senang', score: 8 },
            { emotion: 'takut', score: 5 },
            { emotion: 'marah', score: 2 },
        ],
    },
];

const STATIC_VOICE: VoiceSegment[] = [
    {
        id: 1,
        time: '09:00:08',
        dominant: 'netral',
        tone: 'Tenang',
        scores: [
            { emotion: 'netral', score: 48 },
            { emotion: 'cemas', score: 24 },
            { emotion: 'sedih', score: 15 },
            { emotion: 'senang', score: 7 },
            { emotion: 'takut', score: 4 },
            { emotion: 'marah', score: 2 },
        ],
    },
    {
        id: 2,
        time: '09:00:16',
        dominant: 'cemas',
        tone: 'Bergetar',
        scores: [
            { emotion: 'cemas', score: 44 },
            { emotion: 'netral', score: 26 },
            { emotion: 'sedih', score: 18 },
            { emotion: 'senang', score: 6 },
            { emotion: 'takut', score: 4 },
            { emotion: 'marah', score: 2 },
        ],
    },
    {
        id: 3,
        time: '09:00:28',
        dominant: 'sedih',
        tone: 'Lesu',
        scores: [
            { emotion: 'sedih', score: 40 },
            { emotion: 'cemas', score: 28 },
            { emotion: 'netral', score: 20 },
            { emotion: 'senang', score: 5 },
            { emotion: 'takut', score: 5 },
            { emotion: 'marah', score: 2 },
        ],
    },
];

const STATIC_LLM: TranscriptEntry[] = [
    {
        id: 1,
        time: '09:00:12',
        dominant: 'cemas',
        text: '"Saya merasa kurang tidur belakangan ini dan sering khawatir soal pekerjaan..."',
        notes: 'Klien menunjukkan tanda kecemasan generalisasi. Pola bicara lambat dengan jeda panjang mengindikasikan beban emosional yang signifikan.',
    },
    {
        id: 2,
        time: '09:00:25',
        dominant: 'sedih',
        text: '"Rasanya seperti tidak ada yang memahami apa yang saya rasakan sekarang."',
        notes: 'Ekspresi perasaan kesepian dan isolasi sosial. Perlu eksplorasi lebih lanjut mengenai sistem dukungan klien.',
    },
    {
        id: 3,
        time: '09:00:38',
        dominant: 'cemas',
        text: '"Saya takut kalau kondisi ini tidak kunjung membaik meski sudah coba berbagai cara."',
        notes: 'Ketakutan terhadap stagnasi terapeutik terdeteksi. Perlu penyesuaian strategi dan penetapan ekspektasi yang realistis.',
    },
];

// ─── Sub-components ───────────────────────────────────────────────────────────

function EmotionBadge({ emotion }: { emotion: Emotion }) {
    return (
        <Badge className={`text-xs ${EMOTION_COLORS[emotion]}`}>
            {EMOTION_LABELS[emotion]}
        </Badge>
    );
}

function EmotionBar({ emotion, score }: { emotion: Emotion; score: number }) {
    return (
        <div className="flex items-center gap-2">
            <span className="w-12 shrink-0 text-xs text-muted-foreground">
                {EMOTION_LABELS[emotion]}
            </span>
            <div className="relative h-1.5 flex-1 rounded-full bg-muted">
                <div
                    className={`h-full rounded-full transition-all duration-500 ${EMOTION_BAR_COLORS[emotion]}`}
                    style={{ width: `${score}%` }}
                />
            </div>
            <span className="w-7 text-right text-xs text-muted-foreground">
                {score}%
            </span>
        </div>
    );
}

function SummaryDominant({
    emotion,
    label,
}: {
    emotion: Emotion;
    label: string;
}) {
    return (
        <div className="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2">
            <span className="text-xs text-muted-foreground">{label}</span>
            <EmotionBadge emotion={emotion} />
        </div>
    );
}

function EmptyState({ isLive, label }: { isLive: boolean; label: string }) {
    return (
        <div className="flex min-h-[180px] flex-col items-center justify-center gap-2 rounded-md border border-dashed border-muted bg-muted/10 text-sm text-muted-foreground">
            {isLive ? (
                <>
                    <div className="flex gap-1">
                        <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:0ms]" />
                        <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:150ms]" />
                        <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:300ms]" />
                    </div>
                    <span className="text-xs">{label}</span>
                </>
            ) : (
                <span className="text-xs">
                    Tidak ada data untuk ditampilkan
                </span>
            )}
        </div>
    );
}

function CaptureCard({
    capture,
    isLive,
}: {
    capture: VideoCapture;
    isLive: boolean;
}) {
    return (
        <div className="rounded-md border bg-card p-3 text-sm">
            <div className="mb-2 flex items-center justify-between">
                <span className="font-mono text-xs text-muted-foreground">
                    {capture.time}
                </span>
                <div className="flex items-center gap-1.5">
                    <EmotionBadge emotion={capture.dominant} />
                    {isLive && (
                        <span className="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            live
                        </span>
                    )}
                </div>
            </div>
            <div className="space-y-1">
                {capture.scores.slice(0, 4).map((s) => (
                    <EmotionBar
                        key={s.emotion}
                        emotion={s.emotion}
                        score={s.score}
                    />
                ))}
            </div>
        </div>
    );
}

function VoiceCard({
    segment,
    isLive,
}: {
    segment: VoiceSegment;
    isLive: boolean;
}) {
    return (
        <div className="rounded-md border bg-card p-3 text-sm">
            <div className="mb-2 flex items-center justify-between">
                <span className="font-mono text-xs text-muted-foreground">
                    {segment.time}
                </span>
                <div className="flex items-center gap-1.5">
                    <span className="rounded-md border px-1.5 py-0.5 text-xs text-muted-foreground">
                        {segment.tone}
                    </span>
                    <EmotionBadge emotion={segment.dominant} />
                    {isLive && (
                        <span className="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            live
                        </span>
                    )}
                </div>
            </div>
            <div className="space-y-1">
                {segment.scores.slice(0, 4).map((s) => (
                    <EmotionBar
                        key={s.emotion}
                        emotion={s.emotion}
                        score={s.score}
                    />
                ))}
            </div>
        </div>
    );
}

function LlmCard({
    entry,
    isLive,
}: {
    entry: TranscriptEntry;
    isLive: boolean;
}) {
    return (
        <div className="rounded-md border bg-card p-3 text-sm">
            <div className="mb-2 flex items-center justify-between">
                <span className="font-mono text-xs text-muted-foreground">
                    {entry.time}
                </span>
                <div className="flex items-center gap-1.5">
                    <EmotionBadge emotion={entry.dominant} />
                    {isLive && (
                        <span className="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            live
                        </span>
                    )}
                </div>
            </div>
            <blockquote className="mb-2 border-l-2 border-muted pl-2 text-xs leading-relaxed text-muted-foreground italic">
                {entry.text}
            </blockquote>
            <p className="text-xs leading-relaxed text-foreground/80">
                {entry.notes}
            </p>
        </div>
    );
}

// ─── Main component ───────────────────────────────────────────────────────────

interface Props {
    status: ConsultationStatus;
}

export default function ConsultationAnalysis({ status }: Props) {
    const isLive = status === 'ongoing';
    const hasSession = status === 'ongoing' || status === 'completed';

    const [captures, setCaptures] = useState<VideoCapture[]>(
        status === 'completed' ? STATIC_CAPTURES : [],
    );
    const [voiceSegments, setVoiceSegments] = useState<VoiceSegment[]>(
        status === 'completed' ? STATIC_VOICE : [],
    );
    const [transcripts, setTranscripts] = useState<TranscriptEntry[]>(
        status === 'completed' ? STATIC_LLM : [],
    );

    const captureIdRef = useRef(1);
    const voiceIdRef = useRef(1);
    const transcriptIdRef = useRef(1);

    useEffect(() => {
        if (status !== 'ongoing') {
            return;
        }

        const captureTimer = setInterval(() => {
            const scores = randomScores();
            setCaptures((prev) => [
                {
                    id: captureIdRef.current++,
                    time: formatNow(),
                    dominant: topEmotion(scores),
                    scores,
                },
                ...prev.slice(0, 9),
            ]);
        }, 5000);

        const voiceTimer = setInterval(() => {
            const scores = randomScores();
            setVoiceSegments((prev) => [
                {
                    id: voiceIdRef.current++,
                    time: formatNow(),
                    dominant: topEmotion(scores),
                    tone: TONES[Math.floor(Math.random() * TONES.length)],
                    scores,
                },
                ...prev.slice(0, 6),
            ]);
        }, 8000);

        const llmTimer = setInterval(() => {
            const scores = randomScores();
            const sample =
                TRANSCRIPT_POOL[
                    Math.floor(Math.random() * TRANSCRIPT_POOL.length)
                ];
            setTranscripts((prev) => [
                {
                    id: transcriptIdRef.current++,
                    time: formatNow(),
                    dominant: topEmotion(scores),
                    text: sample.text,
                    notes: sample.notes,
                },
                ...prev.slice(0, 4),
            ]);
        }, 18000);

        return () => {
            clearInterval(captureTimer);
            clearInterval(voiceTimer);
            clearInterval(llmTimer);
        };
    }, [status]);

    if (!hasSession) {
        return null;
    }

    const dominantFace = overallDominant(captures);
    const dominantVoice = overallDominant(voiceSegments);
    const dominantLlm = overallDominant(transcripts);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <Activity className="size-4" />
                    Analisa Emosi Sesi
                    {isLive && (
                        <span className="ml-auto flex items-center gap-1.5 text-xs font-normal text-red-500">
                            <span className="relative flex size-2">
                                <span className="absolute inline-flex size-full animate-ping rounded-full bg-red-400 opacity-75" />
                                <span className="relative inline-flex size-2 rounded-full bg-red-500" />
                            </span>
                            Live
                        </span>
                    )}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Tabs defaultValue="face">
                    <TabsList className="w-full">
                        <TabsTrigger
                            value="face"
                            className="flex flex-1 items-center gap-1.5"
                        >
                            <Camera className="size-3.5" />
                            Wajah
                        </TabsTrigger>
                        <TabsTrigger
                            value="voice"
                            className="flex flex-1 items-center gap-1.5"
                        >
                            <Mic className="size-3.5" />
                            Suara
                        </TabsTrigger>
                        <TabsTrigger
                            value="llm"
                            className="flex flex-1 items-center gap-1.5"
                        >
                            <Brain className="size-3.5" />
                            LLM
                        </TabsTrigger>
                    </TabsList>

                    {/* ── Face emotion tab ── */}
                    <TabsContent value="face" className="mt-3 space-y-3">
                        {captures.length === 0 ? (
                            <EmptyState
                                isLive={isLive}
                                label="Menunggu tangkapan frame video…"
                            />
                        ) : (
                            <>
                                {dominantFace && !isLive && (
                                    <SummaryDominant
                                        emotion={dominantFace}
                                        label="Emosi dominan keseluruhan"
                                    />
                                )}
                                <ScrollArea className="h-[280px]">
                                    <div className="space-y-2 pr-3">
                                        {captures.map((c) => (
                                            <CaptureCard
                                                key={c.id}
                                                capture={c}
                                                isLive={isLive}
                                            />
                                        ))}
                                    </div>
                                </ScrollArea>
                            </>
                        )}
                    </TabsContent>

                    {/* ── Voice emotion tab ── */}
                    <TabsContent value="voice" className="mt-3 space-y-3">
                        {voiceSegments.length === 0 ? (
                            <EmptyState
                                isLive={isLive}
                                label="Menunggu analisa segmen suara…"
                            />
                        ) : (
                            <>
                                {dominantVoice && !isLive && (
                                    <SummaryDominant
                                        emotion={dominantVoice}
                                        label="Emosi dominan keseluruhan"
                                    />
                                )}
                                <ScrollArea className="h-[280px]">
                                    <div className="space-y-2 pr-3">
                                        {voiceSegments.map((s) => (
                                            <VoiceCard
                                                key={s.id}
                                                segment={s}
                                                isLive={isLive}
                                            />
                                        ))}
                                    </div>
                                </ScrollArea>
                            </>
                        )}
                    </TabsContent>

                    {/* ── LLM analysis tab ── */}
                    <TabsContent value="llm" className="mt-3 space-y-3">
                        {transcripts.length === 0 ? (
                            <EmptyState
                                isLive={isLive}
                                label="Menunggu transkripsi dan analisa LLM…"
                            />
                        ) : (
                            <>
                                {dominantLlm && !isLive && (
                                    <SummaryDominant
                                        emotion={dominantLlm}
                                        label="Emosi dominan keseluruhan"
                                    />
                                )}
                                <ScrollArea className="h-[280px]">
                                    <div className="space-y-2 pr-3">
                                        {transcripts.map((t) => (
                                            <LlmCard
                                                key={t.id}
                                                entry={t}
                                                isLive={isLive}
                                            />
                                        ))}
                                    </div>
                                </ScrollArea>
                            </>
                        )}
                    </TabsContent>
                </Tabs>
            </CardContent>
        </Card>
    );
}
