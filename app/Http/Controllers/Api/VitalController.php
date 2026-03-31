<?php

namespace App\Http\Controllers\Api;

use App\Events\VitalDataSynced;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncVitalRequest;
use App\Http\Resources\WearableDataResource;
use App\Models\WearableData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    public function sync(SyncVitalRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $vital = WearableData::create([
            'user_id'      => $request->user()->id,
            'hrv_score'    => $validated['hrv_score'],
            'heart_rate'   => $validated['heart_rate'],
            'stress_index' => $validated['stress_index'] ?? round(100 - $validated['hrv_score'], 2),
            'device_type'  => $validated['device_type'] ?? null,
            'is_simulated' => $validated['is_simulated'] ?? false,
            'recorded_at'  => $validated['recorded_at'] ?? now(),
        ]);

        VitalDataSynced::dispatch($request->user(), $vital);

        return response()->json([
            'success' => true,
            'message' => 'Data vital berhasil disimpan',
            'data'    => new WearableDataResource($vital),
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function latest(Request $request): JsonResponse
    {
        $vital = $request->user()
            ->wearableData()
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $vital ? new WearableDataResource($vital) : null,
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $query = $request->user()
            ->wearableData()
            ->orderByDesc('recorded_at');

        if ($from) {
            $query->where('recorded_at', '>=', $from);
        }

        if ($to) {
            $query->where('recorded_at', '<=', $to);
        }

        $vitals = $query->paginate(50);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => WearableDataResource::collection($vitals),
            'meta'    => [
                'timestamp'    => now()->toIso8601String(),
                'current_page' => $vitals->currentPage(),
                'last_page'    => $vitals->lastPage(),
                'per_page'     => $vitals->perPage(),
                'total'        => $vitals->total(),
            ],
        ]);
    }
}
