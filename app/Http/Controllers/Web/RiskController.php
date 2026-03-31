<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\MentalStatusLogResource;
use App\Http\Resources\PatientResource;
use App\Models\MentalStatusLog;
use App\Models\RiskThreshold;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class RiskController extends Controller
{
    public function index(): Response
    {
        $criticalPatients = User::where('role', 'patient')
            ->whereHas('mentalStatusLogs', fn ($q) => $q->where('risk_level', 'critical')->whereDate('created_at', '>=', now()->subDays(7)))
            ->with(['profile', 'latestMentalStatus', 'latestWearable'])
            ->get();

        $highRiskPatients = User::where('role', 'patient')
            ->whereHas('mentalStatusLogs', fn ($q) => $q->where('risk_level', 'high')->whereDate('created_at', '>=', now()->subDays(7)))
            ->with(['profile', 'latestMentalStatus', 'latestWearable'])
            ->get();

        $riskDistribution = MentalStatusLog::selectRaw("risk_level, count(*) as count")
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level');

        $thresholds = RiskThreshold::where('is_active', true)->get();

        return Inertia::render('risk/dashboard', [
            'criticalPatients'  => PatientResource::collection($criticalPatients),
            'highRiskPatients'  => PatientResource::collection($highRiskPatients),
            'riskDistribution'  => $riskDistribution,
            'thresholds'        => $thresholds,
        ]);
    }
}
