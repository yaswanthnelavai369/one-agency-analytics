<?php

namespace App\Services\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
    }

    public function generateSecret(User $user): string
    {
        $secret = $this->engine->generateSecretKey();

        $user->forceFill(['two_factor_secret' => encrypt($secret)])->save();

        return $secret;
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        return $this->engine->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }

    public function confirm(User $user, string $oneTimeCode): bool
    {
        $secret = decrypt($user->two_factor_secret);

        if (! $this->engine->verifyKey($secret, $oneTimeCode)) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
        ])->save();

        return true;
    }

    public function verify(User $user, string $oneTimeCode): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        return $this->engine->verifyKey(decrypt($user->two_factor_secret), $oneTimeCode);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_diff($codes, [$code]));
        $user->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($remaining))])->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    protected function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(bin2hex(random_bytes(5))))
            ->all();
    }
}
