<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Agency\AgencyService;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $auth,
        protected AgencyService $agencies,
    ) {}

    /** Sign-up flow: creates the owner user + their Agency in one step. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $owner = $this->auth->registerAgencyOwner($data);
        $agency = $this->agencies->createForOwner($owner, ['name' => $data['agency_name']]);

        $token = $this->auth->issueToken($owner, $request->userAgent() ?? 'api');

        return response()->json([
            'user' => new UserResource($owner->load('roles')),
            'agency' => $agency->uuid,
            'token' => $token->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->auth->attemptLogin($request->string('email'), $request->string('password'));

        if (! $user) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'two_factor_required' => true,
                'challenge_token' => encrypt($user->id), // short-lived, consumed by TwoFactorController::verify
            ]);
        }

        $this->auth->recordLogin($user, $request->ip());
        $token = $this->auth->issueToken($user, $request->input('device_name', 'api'));

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user(), $request->user()->currentAccessToken()->id ?? null);

        return response()->json(['message' => 'Logged out.']);
    }

    public function logoutAllDevices(Request $request): JsonResponse
    {
        $this->auth->logoutAllDevices($request->user());

        return response()->json(['message' => 'Logged out of all devices.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('roles')));
    }
}
