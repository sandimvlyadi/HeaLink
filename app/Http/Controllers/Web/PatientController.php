<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatHistoryResource;
use App\Http\Resources\MentalStatusLogResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\SleepLogResource;
use App\Http\Resources\WearableDataResource;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(): Response
    {
        $patients = User::where('role', 'patient')
            ->with(['profile', 'latestMentalStatus', 'latestWearable'])
            ->latest()
            ->paginate(20);

        return Inertia::render('patients/index', [
            'patients' => PatientResource::collection($patients),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['profile', 'latestMentalStatus', 'latestWearable', 'latestScreening']);

        return Inertia::render('patients/show', [
            // Resolve resource eagerly — needed for page title and header rendering
            'patient' => (new PatientResource($user))->resolve(),

            // History data is deferred — loads after the initial page paint
            'wearableHistory' => Inertia::defer(fn () => WearableDataResource::collection(
                $user->wearableData()->orderBy('recorded_at', 'desc')->limit(48)->get()
            )->resolve()),

            'sleepHistory' => Inertia::defer(fn () => SleepLogResource::collection(
                $user->sleepLogs()->orderBy('sleep_date', 'desc')->limit(30)->get()
            )->resolve()),

            'riskHistory' => Inertia::defer(fn () => MentalStatusLogResource::collection(
                $user->mentalStatusLogs()->latest()->limit(10)->get()
            )->resolve()),
        ]);
    }

    public function chatLog(User $user): Response
    {
        $chatHistories = ChatHistoryResource::collection(
            $user->chatHistories()->latest()->paginate(50)
        );

        return Inertia::render('patients/chat-log', [
            'patient' => (new PatientResource($user->load('profile')))->resolve(),
            'chatHistories' => $chatHistories->response()->getData(true),
        ]);
    }
}
