<?php

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

/*
 * Private channel for each doctor — only the authenticated medic whose UUID
 * matches the channel parameter may subscribe.
 */
Broadcast::channel('doctor.{doctorUuid}', function (User $user, string $doctorUuid) {
    return $user->uuid === $doctorUuid && $user->role === 'medic';
});

/*
 * Private channel for each active consultation — accessible by the patient
 * or the medic involved in that consultation.
 */
Broadcast::channel('consultation.{consultationUuid}', function (User $user, string $consultationUuid) {
    $consultation = Consultation::where('uuid', $consultationUuid)->first();

    return $consultation && in_array($user->id, [$consultation->patient_id, $consultation->medic_id]);
});
