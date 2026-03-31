<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Models\Consultation;
use App\Models\MentalStatusLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $patientIds = User::where('role', 'patient')->pluck('id');

        $patients = User::where('role', 'patient')
            ->where('is_active', true)
            ->with(['profile', 'latestMentalStatus', 'latestWearable'])
            ->latest()
            ->paginate(15);

        $stats = [
            'total_patients'        => $patientIds->count(),
            'high_risk_patients'    => MentalStatusLog::whereIn('risk_level', ['high', 'critical'])
                ->whereIn('user_id', $patientIds)
                ->whereDate('created_at', '>=', now()->subDay())
                ->distinct('user_id')
                ->count(),
            'critical_patients'     => MentalStatusLog::where('risk_level', 'critical')
                ->whereIn('user_id', $patientIds)
                ->whereDate('created_at', '>=', now()->subDay())
                ->distinct('user_id')
                ->count(),
            'pending_consultations' => Consultation::where('status', 'pending')->count(),
        ];

        return Inertia::render('dashboard', [
            'patients' => PatientResource::collection($patients),
            'stats'    => $stats,
        ]);
    }
}
