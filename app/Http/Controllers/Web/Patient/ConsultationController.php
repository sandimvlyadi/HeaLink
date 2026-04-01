<?php

namespace App\Http\Controllers\Web\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Patient\StoreConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Http\Resources\UserResource;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    public function index(Request $request): Response
    {
        $consultations = Consultation::with(['medic.profile'])
            ->where('patient_id', $request->user()->id)
            ->latest('scheduled_at')
            ->paginate(15);

        return Inertia::render('patient/consultations/index', [
            'consultations' => ConsultationResource::collection($consultations),
        ]);
    }

    public function create(): Response
    {
        $medics = User::where('role', 'medic')
            ->where('is_active', true)
            ->with('profile')
            ->get();

        return Inertia::render('patient/consultations/create', [
            'medics' => $medics->map(fn (User $user) => (new UserResource($user))->resolve()),
        ]);
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        $medic = User::where('uuid', $request->validated('medic_id'))->firstOrFail();

        Consultation::create([
            'patient_id' => $request->user()->id,
            'medic_id' => $medic->id,
            'status' => 'pending',
            'scheduled_at' => $request->validated('scheduled_at'),
        ]);

        return to_route('patient.consultations.index')->with('status', 'booked');
    }

    public function show(Request $request, Consultation $consultation): Response
    {
        if ($consultation->patient_id !== $request->user()->id) {
            abort(403);
        }

        $consultation->load(['medic.profile']);

        return Inertia::render('patient/consultations/show', [
            'consultation' => (new ConsultationResource($consultation))->resolve(),
        ]);
    }

    public function cancel(Request $request, Consultation $consultation): RedirectResponse
    {
        if ($consultation->patient_id !== $request->user()->id) {
            abort(403);
        }

        if ($consultation->status !== 'pending') {
            return back()->withErrors(['status' => 'Konsultasi tidak dapat dibatalkan.']);
        }

        $consultation->update(['status' => 'cancelled']);

        return to_route('patient.consultations.index')->with('status', 'cancelled');
    }
}
