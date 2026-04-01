<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new patient account.
     *
     * Creates a new user with role `patient`, initialises an empty profile,
     * and returns a Sanctum Bearer token ready for immediate use.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => 'patient',
        ]);

        UserProfile::create(['user_id' => $user->id]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => new UserResource($user->load('profile')),
                'token' => $token,
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    /**
     * Authenticate and obtain a Bearer token.
     *
     * Validates email/password credentials. Returns 403 if the account is
     * inactive. On success, returns the user profile and a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif. Hubungi administrator.',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => new UserResource($user->load('profile')),
                'token' => $token,
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Revoke the current Bearer token.
     *
     * Deletes the current access token. The token will no longer be valid
     * for subsequent requests.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Get the authenticated user's profile.
     *
     * Returns the current user object including the nested profile sub-object.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => new UserResource($request->user()->load('profile')),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * All fields are optional. Only the provided fields are updated.
     * The profile record is created automatically if it doesn't exist yet.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        $profileData = array_filter([
            'gender' => $validated['gender'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'job' => $validated['job'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ], fn ($v) => ! is_null($v));

        if (! empty($profileData)) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => new UserResource($user->fresh()->load('profile')),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
