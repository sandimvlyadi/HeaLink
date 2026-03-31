<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MentalStatusLog;
use App\Models\User;
use App\Models\WearableData;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $summary = [
            'total_patients'      => User::where('role', 'patient')->count(),
            'total_consultations' => Consultation::count(),
            'total_risk_logs'     => MentalStatusLog::count(),
            'total_wearable_data' => WearableData::count(),
        ];

        $riskTrend = MentalStatusLog::selectRaw("DATE(created_at) as date, risk_level, count(*) as count")
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date', 'risk_level')
            ->orderBy('date')
            ->get();

        return Inertia::render('reports/index', [
            'summary'   => $summary,
            'riskTrend' => $riskTrend,
        ]);
    }
}
