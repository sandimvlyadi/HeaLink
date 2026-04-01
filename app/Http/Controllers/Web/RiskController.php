<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
        $since = now()->subDays(7);
        $withRelations = ['profile', 'latestMentalStatus', 'latestWearable'];

        $criticalPatients = User::where('role', 'patient')
            ->whereHas('mentalStatusLogs', fn ($q) => $q
                ->where('risk_level', 'critical')
                ->whereDate('created_at', '>=', $since)
            )
            ->with($withRelations)
            ->get();

        $highRiskPatients = User::where('role', 'patient')
            ->whereHas('mentalStatusLogs', fn ($q) => $q
                ->where('risk_level', 'high')
                ->whereDate('created_at', '>=', $since)
            )
            ->with($withRelations)
            ->get();

        $riskDistribution = MentalStatusLog::selectRaw('risk_level, count(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level');

        $thresholds = RiskThreshold::where('is_active', true)->get();

        return Inertia::render('risk/dashboard', [
            'criticalPatients' => PatientResource::collection($criticalPatients),
            'highRiskPatients' => PatientResource::collection($highRiskPatients),
            'riskDistribution' => $riskDistribution,
            'thresholds' => $thresholds,
        ]);
    }
}
