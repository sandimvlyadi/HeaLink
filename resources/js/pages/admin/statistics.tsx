import { index as adminStatsIndex } from '@/actions/App/Http/Controllers/Web/Admin/StatisticsController';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { TrendingUp } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface MonthlyCount {
    month: string;
    count: number;
}

interface Props {
    usersByRole: Record<string, number>;
    consultationsByStatus: Record<string, number>;
    riskByLevel: Record<string, number>;
    newPatientsPerMonth: MonthlyCount[];
}

const roleColors: Record<string, string> = {
    patient: '#0ea5e9',
    medic: '#8b5cf6',
    admin: '#f43f5e',
};
const roleLabel: Record<string, string> = {
    patient: 'Pasien',
    medic: 'Dokter',
    admin: 'Admin',
};

const statusColors: Record<string, string> = {
    pending: '#f59e0b',
    ongoing: '#3b82f6',
    completed: '#10b981',
    cancelled: '#94a3b8',
};
const statusLabel: Record<string, string> = {
    pending: 'Menunggu',
    ongoing: 'Berlangsung',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

const riskFillColors: Record<string, string> = {
    low: '#10b981',
    medium: '#f59e0b',
    high: '#f97316',
    critical: '#ef4444',
};
const riskLabel: Record<string, string> = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
    critical: 'Kritis',
};

function toChartArray(
    data: Record<string, number>,
    labelMap: Record<string, string>,
    colorMap: Record<string, string>,
) {
    return Object.entries(data).map(([key, value]) => ({
        name: labelMap[key] ?? key,
        value,
        fill: colorMap[key] ?? '#94a3b8',
    }));
}

export default function AdminStatistics({
    usersByRole,
    consultationsByStatus,
    riskByLevel,
    newPatientsPerMonth,
}: Props) {
    const usersChartData = toChartArray(usersByRole, roleLabel, roleColors);
    const consultationsChartData = toChartArray(
        consultationsByStatus,
        statusLabel,
        statusColors,
    );
    const riskChartData = toChartArray(riskByLevel, riskLabel, riskFillColors);

    const monthlyData = newPatientsPerMonth.map((item) => ({
        month: item.month,
        pasien: item.count,
    }));

    return (
        <>
            <Head title="Statistik — HeaLink Admin" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Statistik Platform
                    </h1>
                    <p className="text-muted-foreground">
                        Gambaran menyeluruh data platform HeaLink
                    </p>
                </div>

                {/* Top row — 3 pie/bar charts */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Users by role */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Pengguna per Peran
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={200}>
                                <PieChart>
                                    <Pie
                                        data={usersChartData}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={50}
                                        outerRadius={80}
                                        paddingAngle={3}
                                        dataKey="value"
                                        nameKey="name"
                                        label={({ name, value }) =>
                                            `${name}: ${value}`
                                        }
                                        labelLine={false}
                                    >
                                        {usersChartData.map((entry, i) => (
                                            <Cell key={i} fill={entry.fill} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Consultations by status */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Status Konsultasi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={200}>
                                <BarChart
                                    data={consultationsChartData}
                                    margin={{
                                        left: -20,
                                        right: 4,
                                        top: 4,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-border"
                                    />
                                    <XAxis
                                        dataKey="name"
                                        tick={{ fontSize: 11 }}
                                    />
                                    <YAxis
                                        tick={{ fontSize: 11 }}
                                        allowDecimals={false}
                                    />
                                    <Tooltip />
                                    <Bar
                                        dataKey="value"
                                        radius={[4, 4, 0, 0]}
                                        name="Konsultasi"
                                    >
                                        {consultationsChartData.map(
                                            (entry, i) => (
                                                <Cell
                                                    key={i}
                                                    fill={entry.fill}
                                                />
                                            ),
                                        )}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Risk distribution (30 days) */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Distribusi Risiko (30 Hari)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={200}>
                                <BarChart
                                    data={riskChartData}
                                    margin={{
                                        left: -20,
                                        right: 4,
                                        top: 4,
                                        bottom: 0,
                                    }}
                                >
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-border"
                                    />
                                    <XAxis
                                        dataKey="name"
                                        tick={{ fontSize: 11 }}
                                    />
                                    <YAxis
                                        tick={{ fontSize: 11 }}
                                        allowDecimals={false}
                                    />
                                    <Tooltip />
                                    <Bar
                                        dataKey="value"
                                        radius={[4, 4, 0, 0]}
                                        name="Log"
                                    >
                                        {riskChartData.map((entry, i) => (
                                            <Cell key={i} fill={entry.fill} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* New patients per month */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="size-5" />
                            Pasien Baru per Bulan (12 Bulan Terakhir)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {monthlyData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={280}>
                                <LineChart
                                    data={monthlyData}
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
                                        dataKey="month"
                                        tick={{ fontSize: 11 }}
                                    />
                                    <YAxis
                                        tick={{ fontSize: 11 }}
                                        allowDecimals={false}
                                    />
                                    <Tooltip />
                                    <Legend />
                                    <Line
                                        type="monotone"
                                        dataKey="pasien"
                                        stroke="#0ea5e9"
                                        strokeWidth={2}
                                        dot={{ r: 3 }}
                                        name="Pasien Baru"
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="flex h-[280px] items-center justify-center text-sm text-muted-foreground">
                                Belum ada data pasien baru
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminStatistics.layout = () => ({
    breadcrumbs: [
        { title: 'Dashboard', href: '/' },
        { title: 'Admin', href: adminStatsIndex.url() },
        { title: 'Statistik', href: adminStatsIndex.url() },
    ],
});
