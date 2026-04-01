<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * List consultations for the authenticated patient.
     *
     * Returns a paginated list of consultations belonging to the current user,
     * ordered by most recently scheduled. Optionally filter by status.
     *
     * @queryParam status string Filter by status. One of: `pending`, `active`, `completed`, `cancelled`. Example: pending
     * @queryParam page int Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $query = Consultation::with(['medic.profile'])
            ->where('patient_id', $request->user()->id)
            ->orderByDesc('scheduled_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $consultations = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => ConsultationResource::collection($consultations),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'current_page' => $consultations->currentPage(),
                'last_page' => $consultations->lastPage(),
                'per_page' => $consultations->perPage(),
                'total' => $consultations->total(),
            ],
        ]);
    }

    /**
     * Get a single consultation detail.
     *
     * Returns full details of a consultation. Only the patient who owns the
     * consultation can access this endpoint.
     */
    public function show(Request $request, Consultation $consultation): JsonResponse
    {
        if ($consultation->patient_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $consultation->load(['medic.profile', 'facialEmotionLogs']);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => new ConsultationResource($consultation),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Book a new consultation.
     *
     * Creates a consultation with status `pending` for the authenticated patient.
     *
     * @bodyParam medic_id string required UUID of the medic. Example: 550e8400-e29b-41d4-a716-446655440000
     * @bodyParam scheduled_at string required ISO 8601 datetime in the future. Example: 2026-05-01T09:00:00
     */
    public function store(StoreConsultationRequest $request): JsonResponse
    {
        $medic = User::where('uuid', $request->validated('medic_id'))->firstOrFail();

        $consultation = Consultation::create([
            'patient_id' => $request->user()->id,
            'medic_id' => $medic->id,
            'status' => 'pending',
            'scheduled_at' => $request->validated('scheduled_at'),
        ]);

        $consultation->load(['medic.profile']);

        return response()->json([
            'success' => true,
            'message' => 'Konsultasi berhasil dibuat',
            'data' => new ConsultationResource($consultation),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    /**
     * Cancel a consultation.
     *
     * Patients can cancel their own `pending` consultations.
     * Medics can cancel their own `pending` or `ongoing` consultations.
     */
    public function cancel(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            if ($consultation->patient_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'meta' => ['timestamp' => now()->toIso8601String()],
                ], 403);
            }

            if ($consultation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya konsultasi dengan status pending yang dapat dibatalkan oleh pasien.',
                    'meta' => ['timestamp' => now()->toIso8601String()],
                ], 422);
            }
        } elseif ($user->role === 'medic') {
            if ($consultation->medic_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'meta' => ['timestamp' => now()->toIso8601String()],
                ], 403);
            }

            if (! in_array($consultation->status, ['pending', 'ongoing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konsultasi tidak dapat dibatalkan.',
                    'meta' => ['timestamp' => now()->toIso8601String()],
                ], 422);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $consultation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Konsultasi berhasil dibatalkan',
            'data' => new ConsultationResource($consultation),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Start a consultation (medic only).
     *
     * Transitions the consultation from `pending` to `ongoing` and records `started_at`.
     * Only the assigned medic can perform this action.
     */
    public function start(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'medic' || $consultation->medic_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        if ($consultation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya konsultasi dengan status pending yang dapat dimulai.',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 422);
        }

        $consultation->update([
            'status' => 'ongoing',
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Konsultasi berhasil dimulai',
            'data' => new ConsultationResource($consultation),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Complete a consultation (medic only).
     *
     * Transitions the consultation from `ongoing` to `completed` and records `ended_at`.
     * Only the assigned medic can perform this action.
     */
    public function complete(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'medic' || $consultation->medic_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        if ($consultation->status !== 'ongoing') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya konsultasi dengan status ongoing yang dapat diselesaikan.',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 422);
        }

        $consultation->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Konsultasi berhasil diselesaikan',
            'data' => new ConsultationResource($consultation),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
