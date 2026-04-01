<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Http\Resources\PatientResource;
use App\Models\Consultation;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $consultations = Consultation::with(['patient.profile', 'medic'])
            ->when($user->role === 'medic', fn ($q) => $q->where('medic_id', $user->id))
            ->latest('scheduled_at')
            ->paginate(15);

        $patients = User::where('role', 'patient')
            ->where('is_active', true)
            ->with('profile')
            ->get();

        return Inertia::render('consultations/index', [
            'consultations' => ConsultationResource::collection($consultations),
            'patients' => PatientResource::collection($patients),
        ]);
    }

    public function room(Consultation $consultation): Response
    {
        $consultation->load(['patient.profile', 'patient.latestMentalStatus', 'medic']);

        return Inertia::render('consultations/room', [
            'consultation' => (new ConsultationResource($consultation))->resolve(),
        ]);
    }
}
