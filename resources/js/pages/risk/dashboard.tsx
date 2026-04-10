import { show } from '@/actions/App/Http/Controllers/Web/PatientController';
import { index as riskIndex } from '@/actions/App/Http/Controllers/Web/RiskController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Patient, RiskThreshold } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    Brain,
    Heart,
    TrendingUp,
    Users,
} from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Props {
    criticalPatients: Patient[];
    highRiskPatients: Patient[];
    riskDistribution: Record<string, number>;
    thresholds: RiskThreshold[];
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

const barFillColors: Record<string, string> = {
    low: '#10b981',
    medium: '#f59e0b',
    high: '#f97316',
    critical: '#ef4444',
};

function PatientRow({ patient }: { patient: Patient }) {
    const risk = patient.latest_mental_status?.risk_level ?? 'low';

    return (
        <div className="flex items-center justify-between py-3">
            <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-full bg-muted text-sm font-semibold">
                    {patient.name.charAt(0).toUpperCase()}
                </div>
                <div>
                    <p className="text-sm font-medium">{patient.name}</p>
                    <p className="text-xs text-muted-foreground">
                        Skor: {patient.latest_mental_status?.risk_score ?? '—'}
                    </p>
                </div>
            </div>
            <div className="flex items-center gap-2">
                <Badge className={`text-xs ${riskColors[risk]}`}>
                    {riskLabel[risk]}
                </Badge>
                <Button asChild variant="ghost" size="sm">
                    <Link href={show({ user: patient.uuid }).url}>Detail</Link>
                </Button>
            </div>
        </div>
    );
}

export default function RiskDashboard({
    criticalPatients,
    highRiskPatients,
    riskDistribution,
    thresholds,
}: Props) {
    const chartData = Object.entries(riskDistribution).map(
        ([level, count]) => ({
            level: riskLabel[level] ?? level,
            jumlah: count,
            fill: barFillColors[level] ?? '#94a3b8',
        }),
    );

    return (
        <>
            <Head title="Monitoring Risiko — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Monitoring Risiko
                    </h1>
                    <p className="text-muted-foreground">
                        Pantau distribusi risiko pasien dalam 30 hari terakhir
                    </p>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Pasien Kritis
                            </CardTitle>
                            <AlertTriangle className="size-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-red-600">
                                {criticalPatients.length}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Dalam 7 hari terakhir
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Risiko Tinggi
                            </CardTitle>
                            <TrendingUp className="size-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold text-orange-600">
                                {highRiskPatients.length}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Dalam 7 hari terakhir
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Total Log Risiko
                            </CardTitle>
                            <Activity className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {Object.values(riskDistribution).reduce(
                                    (a, b) => a + b,
                                    0,
                                )}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Dalam 30 hari terakhir
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Risk Distribution Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Brain className="size-5" />
                                Distribusi Risiko (30 Hari)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {chartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={220}>
                                    <BarChart
                                        data={chartData}
                                        margin={{
                                            top: 4,
                                            right: 4,
                                            left: -16,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            className="stroke-border"
                                        />
                                        <XAxis
                                            dataKey="level"
                                            tick={{ fontSize: 12 }}
                                        />
                                        <YAxis tick={{ fontSize: 12 }} />
                                        <Tooltip
                                            contentStyle={{
                                                borderRadius: '8px',
                                                border: '1px solid hsl(var(--border))',
                                            }}
                                            formatter={(value) => [
                                                value,
                                                'Log',
                                            ]}
                                        />
                                        <Bar
                                            dataKey="jumlah"
                                            radius={[4, 4, 0, 0]}
                                        >
                                            {chartData.map((entry, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={entry.fill}
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex h-[220px] items-center justify-center text-sm text-muted-foreground">
                                    Belum ada data risiko
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Thresholds */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Heart className="size-5" />
                                Konfigurasi Ambang Batas
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {thresholds.length > 0 ? (
                                <div className="divide-y">
                                    {thresholds.map((t, i) => (
                                        <div
                                            key={i}
                                            className="flex items-center justify-between px-6 py-3"
                                        >
                                            <p className="text-sm font-medium capitalize">
                                                {t.parameter_name}
                                            </p>
                                            <div className="space-y-0.5 text-right text-xs text-muted-foreground">
                                                {t.low_max !== null && (
                                                    <div>
                                                        Rendah: ≤ {t.low_max}
                                                    </div>
                                                )}
                                                {t.high_min !== null && (
                                                    <div>
                                                        Tinggi: ≥ {t.high_min}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex h-[180px] items-center justify-center px-6 text-sm text-muted-foreground">
                                    Tidak ada konfigurasi ambang batas aktif
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Patient Lists */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Critical patients */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-red-600">
                                <AlertTriangle className="size-5" />
                                Pasien Kritis
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {criticalPatients.length > 0 ? (
                                <div className="divide-y px-6">
                                    {criticalPatients.map((p) => (
                                        <PatientRow key={p.uuid} patient={p} />
                                    ))}
                                </div>
                            ) : (
                                <div className="flex h-[120px] items-center justify-center text-sm text-muted-foreground">
                                    Tidak ada pasien kritis
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* High-risk patients */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-orange-600">
                                <Users className="size-5" />
                                Risiko Tinggi
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {highRiskPatients.length > 0 ? (
                                <div className="divide-y px-6">
                                    {highRiskPatients.map((p) => (
                                        <PatientRow key={p.uuid} patient={p} />
                                    ))}
                                </div>
                            ) : (
                                <div className="flex h-[120px] items-center justify-center text-sm text-muted-foreground">
                                    Tidak ada pasien risiko tinggi
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

RiskDashboard.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Monitoring Risiko', href: riskIndex.url() },
    ],
});
