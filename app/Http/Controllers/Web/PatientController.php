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

        $wearableHistory = WearableDataResource::collection(
            $user->wearableData()->orderBy('recorded_at', 'desc')->limit(48)->get()
        );

        $sleepHistory = SleepLogResource::collection(
            $user->sleepLogs()->orderBy('sleep_date', 'desc')->limit(30)->get()
        );

        $riskHistory = MentalStatusLogResource::collection(
            $user->mentalStatusLogs()->latest()->limit(10)->get()
        );

        return Inertia::render('patients/show', [
            // Resolve resources so Inertia props match frontend TS shapes (plain object/array)
            'patient'         => (new PatientResource($user))->resolve(),
            'wearableHistory' => $wearableHistory->resolve(),
            'sleepHistory'    => $sleepHistory->resolve(),
            'riskHistory'     => $riskHistory->resolve(),
        ]);
    }

    public function chatLog(User $user): Response
    {
        $chatHistories = ChatHistoryResource::collection(
            $user->chatHistories()->latest()->paginate(50)
        );

        return Inertia::render('patients/chat-log', [
            'patient'       => (new PatientResource($user->load('profile')))->resolve(),
            'chatHistories' => $chatHistories->response()->getData(true),
        ]);
    }
}
