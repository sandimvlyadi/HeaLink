<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MentalStatusLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentalStatusController extends Controller
{
    /**
     * List mental health risk assessments for the authenticated user.
     *
     * Returns a paginated list of AI-generated mental health risk assessments,
     * ordered by most recent. Each entry includes risk level, risk score,
     * detected emotion, and contributing factors.
     *
     * @queryParam page int Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $logs = $request->user()
            ->mentalStatusLogs()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => MentalStatusLogResource::collection($logs),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get the latest mental health risk assessment.
     *
     * Returns the most recent AI-generated mental health risk assessment
     * for the authenticated user. Returns `null` if no assessment exists yet.
     */
    public function latest(Request $request): JsonResponse
    {
        $log = $request->user()
            ->mentalStatusLogs()
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $log ? new MentalStatusLogResource($log) : null,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
