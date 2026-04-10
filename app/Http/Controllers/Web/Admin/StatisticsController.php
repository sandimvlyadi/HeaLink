<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MentalStatusLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function index(): Response
    {
        $usersByRole = User::selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        $consultationsByStatus = Consultation::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $riskByLevel = MentalStatusLog::selectRaw('risk_level, count(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level');

        $monthExpression = match (config('database.default')) {
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "strftime('%Y-%m', created_at)",
        };

        $newPatientsPerMonth = User::where('role', 'patient')
            ->selectRaw("{$monthExpression} as month, count(*) as count")
            ->whereDate('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('admin/statistics', [
            'usersByRole' => $usersByRole,
            'consultationsByStatus' => $consultationsByStatus,
            'riskByLevel' => $riskByLevel,
            'newPatientsPerMonth' => $newPatientsPerMonth,
        ]);
    }
}
