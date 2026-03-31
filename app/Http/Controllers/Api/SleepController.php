<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSleepRequest;
use App\Http\Resources\SleepLogResource;
use App\Models\SleepLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SleepController extends Controller
{
    public function store(StoreSleepRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $sleepLog = SleepLog::updateOrCreate(
            [
                'user_id'    => $user->id,
                'sleep_date' => $validated['sleep_date'],
            ],
            $validated + ['user_id' => $user->id],
        );

        $statusCode = $sleepLog->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'success' => true,
            'message' => 'Data tidur berhasil disimpan',
            'data'    => new SleepLogResource($sleepLog),
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ], $statusCode);
    }

    public function history(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $query = $request->user()
            ->sleepLogs()
            ->orderByDesc('sleep_date');

        if ($from) {
            $query->where('sleep_date', '>=', $from);
        }

        if ($to) {
            $query->where('sleep_date', '<=', $to);
        }

        $logs = $query->paginate(30);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => SleepLogResource::collection($logs),
            'meta'    => [
                'timestamp'    => now()->toIso8601String(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
