<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactor,
        protected AuthService $auth,
    ) {}

    /** Step 1 (setup): generate a secret + QR code for the authenticated user to scan. */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        $secret = $this->twoFactor->generateSecret($user);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $this->twoFactor->qrCodeUrl($user, $secret),
        ]);
    }

    /** Step 2 (setup): confirm the code from the authenticator app to enable 2FA. */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! $this->twoFactor->confirm($request->user(), $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'Invalid authentication code.']);
        }

        return response()->json(['message' => 'Two-factor authentication enabled.']);
    }

    /** Step for login: exchange the challenge token + OTP for a real API token. */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $userId = decrypt($request->string('challenge_token'));
        $user = User::findOrFail($userId);

        $verified = $this->twoFactor->verify($user, $request->string('code'))
            || $this->twoFactor->verifyRecoveryCode($user, $request->string('code'));

        if (! $verified) {
            throw ValidationException::withMessages(['code' => 'Invalid authentication code.']);
        }

        $this->auth->recordLogin($user, $request->ip());
        $token = $this->auth->issueToken($user, $request->input('device_name', 'api'));

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token->plainTextToken,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $this->twoFactor->disable($request->user());

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }
}
