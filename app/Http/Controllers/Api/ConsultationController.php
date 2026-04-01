<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
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
}
