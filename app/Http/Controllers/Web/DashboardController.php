<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
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
        $user = auth()->user();

        if ($user->role === 'patient') {
            return $this->patientDashboard($user);
        }

        $patients = User::where('role', 'patient')
            ->where('is_active', true)
            ->with(['profile', 'latestMentalStatus', 'latestWearable'])
            ->latest()
            ->paginate(15);

        $since = now()->subDay();

        $stats = [
            'total_patients' => User::where('role', 'patient')->count(),
            'high_risk_patients' => MentalStatusLog::whereIn('risk_level', ['high', 'critical'])
                ->whereHas('user', fn ($q) => $q->where('role', 'patient'))
                ->whereDate('created_at', '>=', $since)
                ->distinct('user_id')
                ->count(),
            'critical_patients' => MentalStatusLog::where('risk_level', 'critical')
                ->whereHas('user', fn ($q) => $q->where('role', 'patient'))
                ->whereDate('created_at', '>=', $since)
                ->distinct('user_id')
                ->count(),
            'pending_consultations' => Consultation::where('status', 'pending')->count(),
        ];

        return Inertia::render('dashboard', [
            'patients' => PatientResource::collection($patients),
            'stats' => $stats,
        ]);
    }

    private function patientDashboard(User $user): Response
    {
        $recentConsultations = Consultation::with(['medic.profile'])
            ->where('patient_id', $user->id)
            ->latest('scheduled_at')
            ->take(5)
            ->get();

        $patientStats = [
            'pending_consultations' => Consultation::where('patient_id', $user->id)->where('status', 'pending')->count(),
            'total_consultations' => Consultation::where('patient_id', $user->id)->count(),
            'completed_consultations' => Consultation::where('patient_id', $user->id)->where('status', 'completed')->count(),
        ];

        return Inertia::render('dashboard', [
            'recentConsultations' => ConsultationResource::collection($recentConsultations),
            'patientStats' => $patientStats,
        ]);
    }
}
