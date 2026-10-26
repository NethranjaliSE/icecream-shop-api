<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Exchanges email + password for a Passport personal access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => __('auth.failed')], 401);
        }

        $result = $user->createToken($request->validated('device_name', 'api'));

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $result->accessToken,
            'expires_at' => $result->token?->expires_at?->toIso8601String(),
            'user' => new UserResource($user),
        ]);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * POST /api/auth/logout
     *
     * Revokes the access token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()?->revoke();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
