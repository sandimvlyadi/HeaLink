<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicController extends Controller
{
    /**
     * List all active medics.
     *
     * Returns a list of active medical professionals available for consultation.
     *
     * @queryParam search string Search medic by name. Example: dr. budi
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = User::where('role', 'medic')
            ->where('is_active', true)
            ->with('profile')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->query('search').'%');
        }

        $medics = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => UserResource::collection($medics),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
