<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMoodJournalRequest;
use App\Http\Resources\MoodJournalResource;
use App\Models\MoodJournal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoodJournalController extends Controller
{
    public function store(StoreMoodJournalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $journal = MoodJournal::updateOrCreate(
            [
                'user_id'      => $user->id,
                'journal_date' => $validated['journal_date'],
            ],
            $validated + ['user_id' => $user->id],
        );

        $statusCode = $journal->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'success' => true,
            'message' => 'Jurnal mood berhasil disimpan',
            'data'    => new MoodJournalResource($journal),
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ], $statusCode);
    }

    public function index(Request $request): JsonResponse
    {
        $date = $request->query('date');
        $from = $request->query('from');
        $to   = $request->query('to');

        $query = $request->user()
            ->moodJournals()
            ->orderByDesc('journal_date');

        if ($date) {
            $query->where('journal_date', $date);
        } elseif ($from || $to) {
            if ($from) {
                $query->where('journal_date', '>=', $from);
            }
            if ($to) {
                $query->where('journal_date', '<=', $to);
            }
        }

        $journals = $query->paginate(30);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => MoodJournalResource::collection($journals),
            'meta'    => [
                'timestamp'    => now()->toIso8601String(),
                'current_page' => $journals->currentPage(),
                'last_page'    => $journals->lastPage(),
                'per_page'     => $journals->perPage(),
                'total'        => $journals->total(),
            ],
        ]);
    }
}
