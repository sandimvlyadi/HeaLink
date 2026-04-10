<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Http\Resources\PatientResource;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-type StreamPayload array{user_id: string, iss: string, sub: string, iat: int, exp: int}
 */
class ConsultationController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $consultations = Consultation::with(['patient.profile', 'medic'])
            ->when($user->role === 'medic', fn ($q) => $q->where('medic_id', $user->id))
            ->latest('scheduled_at')
            ->paginate(15);

        $patients = User::where('role', 'patient')
            ->where('is_active', true)
            ->with('profile')
            ->get();

        return Inertia::render('consultations/index', [
            'consultations' => ConsultationResource::collection($consultations),
            'patients' => PatientResource::collection($patients),
        ]);
    }

    public function room(Consultation $consultation): Response
    {
        $consultation->load(['patient.profile', 'patient.latestMentalStatus', 'medic']);

        /** @var User $user */
        $user = auth()->user();

        return Inertia::render('consultations/room', [
            'consultation' => (new ConsultationResource($consultation))->resolve(),
            'stream_api_key' => config('services.getstream.key'),
            'stream_token' => $this->generateStreamToken($user),
            'stream_user_id' => $user->uuid,
            'stream_user_name' => $user->name,
        ]);
    }

    private function generateStreamToken(User $user): string
    {
        $apiKey = config('services.getstream.key');
        $apiSecret = config('services.getstream.secret');

        $header = $this->base64UrlEncode((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $now = time();

        $payload = $this->base64UrlEncode((string) json_encode([
            'user_id' => $user->uuid,
            'iss' => $apiKey,
            'sub' => "user/{$user->uuid}",
            'iat' => $now,
            'exp' => $now + (60 * 60 * 24 * 30),
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $apiSecret, true),
        );

        return "{$header}.{$payload}.{$signature}";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function start(Consultation $consultation): RedirectResponse
    {
        $user = auth()->user();

        if ($consultation->medic_id !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        if ($consultation->status !== 'pending') {
            return back()->withErrors(['status' => 'Konsultasi tidak dapat dimulai.']);
        }

        $consultation->update([
            'status' => 'ongoing',
            'started_at' => now(),
        ]);

        return to_route('consultations.room', $consultation->uuid);
    }

    public function cancel(Consultation $consultation): RedirectResponse
    {
        $user = auth()->user();

        if ($consultation->medic_id !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        if (! in_array($consultation->status, ['pending', 'ongoing'])) {
            return back()->withErrors(['status' => 'Konsultasi tidak dapat dibatalkan.']);
        }

        $consultation->update(['status' => 'cancelled']);

        return to_route('consultations.room', $consultation->uuid);
    }

    public function complete(Consultation $consultation): RedirectResponse
    {
        $user = auth()->user();

        if ($consultation->medic_id !== $user->id && $user->role !== 'admin') {
            abort(403);
        }

        if ($consultation->status !== 'ongoing') {
            return back()->withErrors(['status' => 'Konsultasi tidak dapat diselesaikan.']);
        }

        $consultation->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return to_route('consultations.room', $consultation->uuid);
    }
}
