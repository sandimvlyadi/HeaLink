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
    /**
     * Store or update a sleep log entry.
     *
     * Creates a new sleep log for the given `sleep_date`, or updates the
     * existing one if the date already has a record. Returns 201 on creation
     * and 200 on update.
     */
    public function store(StoreSleepRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $sleepLog = SleepLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'sleep_date' => $validated['sleep_date'],
            ],
            $validated + ['user_id' => $user->id],
        );

        $statusCode = $sleepLog->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'success' => true,
            'message' => 'Data tidur berhasil disimpan',
            'data' => new SleepLogResource($sleepLog),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], $statusCode);
    }

    /**
     * List sleep log history.
     *
     * Returns a paginated list of sleep logs ordered by most recent date.
     * Optionally filter by date range using `from` and `to` (YYYY-MM-DD).
     *
     * @queryParam from string Start date filter (YYYY-MM-DD). Example: 2026-03-01
     * @queryParam to string End date filter (YYYY-MM-DD). Example: 2026-03-31
     * @queryParam page int Page number. Example: 1
     */
    public function history(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');

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
            'data' => SleepLogResource::collection($logs),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
