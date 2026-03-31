<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScreeningRequest;
use App\Http\Resources\HealthScreeningResource;
use App\Models\HealthScreening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function upsert(StoreScreeningRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $bmi = null;
        $height = $validated['height_cm'] ?? null;
        $weight = $validated['weight_kg'] ?? null;

        if ($height && $weight && $height > 0) {
            $heightM = $height / 100;
            $bmi = round($weight / ($heightM ** 2), 2);
        }

        $phq9Score = null;
        if (! empty($validated['phq9_answers'])) {
            $phq9Score = (int) array_sum($validated['phq9_answers']);
        }

        $screening = HealthScreening::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, [
                'bmi'        => $bmi,
                'phq9_score' => $phq9Score,
            ]),
        );

        $statusCode = $screening->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'success' => true,
            'message' => 'Data screening berhasil disimpan',
            'data'    => new HealthScreeningResource($screening),
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ], $statusCode);
    }

    public function latest(Request $request): JsonResponse
    {
        $screening = $request->user()
            ->healthScreenings()
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $screening ? new HealthScreeningResource($screening) : null,
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
