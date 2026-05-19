<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var array{name:string, email:string, password:string} $data */
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $user->createToken('api', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var array{email:string, password:string, device_name?:?string} $data */
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], (string) $user->getAttribute('password'))) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $deviceName = $data['device_name'] ?? 'api';
        $token = $user->createToken($deviceName, ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(status: 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Issue a fresh PAT and revoke the current one. Stateless rotation:
     * the client should swap the bearer header before its next call.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $name = (string) ($request->input('device_name', 'api'));
        $new = $user->createToken($name, ['*'], now()->addDays(30))->plainTextToken;

        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        return response()->json(['token' => $new]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        // Tokens, oauth_accounts, tracked_creators, alerts, export_requests
        // all cascade via FK; shared creator_profiles (no PII) are retained.
        $user->tokens()->delete();
        $user->delete();

        return response()->json(status: 204);
    }
}
