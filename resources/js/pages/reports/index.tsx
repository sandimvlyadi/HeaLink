import { index as reportsIndex } from '@/actions/App/Http/Controllers/Web/ReportController';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { Activity, BarChart3, Brain, FileText, Users } from 'lucide-react';
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface RiskTrendItem {
    date: string;
    risk_level: string;
    count: number;
}

interface Summary {
    total_patients: number;
    total_consultations: number;
    total_risk_logs: number;
    total_wearable_data: number;
}

interface Props {
    summary: Summary;
    riskTrend: RiskTrendItem[];
}

const riskLineColors: Record<string, string> = {
    low: '#10b981',
    medium: '#f59e0b',
    high: '#f97316',
    critical: '#ef4444',
};

const riskLabelMap: Record<string, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

function buildChartData(riskTrend: RiskTrendItem[]) {
    const byDate: Record<string, Record<string, number>> = {};
    for (const item of riskTrend) {
        if (!byDate[item.date]) {
            byDate[item.date] = {};
        }
        byDate[item.date][item.risk_level] = item.count;
    }
    return Object.entries(byDate)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([date, levels]) => ({
            date: new Date(date).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
            }),
            ...levels,
        }));
}

export default function ReportsIndex({ summary, riskTrend }: Props) {
    const chartData = buildChartData(riskTrend);
    const riskLevels = [...new Set(riskTrend.map((r) => r.risk_level))];

    return (
        <>
            <Head title="Laporan — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Laporan
                    </h1>
                    <p className="text-muted-foreground">
                        Ringkasan data dan tren risiko platform
                    </p>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Total Pasien
                            </CardTitle>
                            <Users className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {summary.total_patients.toLocaleString('id-ID')}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Konsultasi
                            </CardTitle>
                            <FileText className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {summary.total_consultations.toLocaleString(
                                    'id-ID',
                                )}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Log Risiko
                            </CardTitle>
                            <Brain className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {summary.total_risk_logs.toLocaleString(
                                    'id-ID',
                                )}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Data Wearable
                            </CardTitle>
                            <Activity className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">
                                {summary.total_wearable_data.toLocaleString(
                                    'id-ID',
                                )}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Risk Trend Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BarChart3 className="size-5" />
                            Tren Risiko 30 Hari Terakhir
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {chartData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart
                                    data={chartData}
                                    margin={{
                                        top: 4,
                                        right: 12,
                                        left: -16,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-border"
                                    />
                                    <XAxis
                                        dataKey="date"
                                        tick={{ fontSize: 11 }}
                                        interval="preserveStartEnd"
                                    />
                                    <YAxis
                                        tick={{ fontSize: 11 }}
                                        allowDecimals={false}
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            borderRadius: '8px',
                                            border: '1px solid hsl(var(--border))',
                                        }}
                                    />
                                    <Legend
                                        formatter={(value) =>
                                            riskLabelMap[value] ?? value
                                        }
                                    />
                                    {riskLevels.map((level) => (
                                        <Line
                                            key={level}
                                            type="monotone"
                                            dataKey={level}
                                            stroke={
                                                riskLineColors[level] ??
                                                '#94a3b8'
                                            }
                                            strokeWidth={2}
                                            dot={false}
                                            name={level}
                                        />
                                    ))}
                                </LineChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="flex h-[300px] items-center justify-center text-sm text-muted-foreground">
                                Belum ada data tren risiko
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ReportsIndex.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Laporan', href: reportsIndex.url() },
    ],
});
