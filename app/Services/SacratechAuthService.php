<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class SacratechAuthService
{
    public function fetchUserById(int $id): ?object
    {
        $table = env('SACRATECH_USERS_TABLE', 'usuarios');

        return DB::connection('sacratech_contas')
            ->table($table)
            ->where('id', $id)
            ->first();
    }

    public function fetchUserByEmail(string $email): ?object
    {
        $table = env('SACRATECH_USERS_TABLE', 'usuarios');

        return DB::connection('sacratech_contas')
            ->table($table)
            ->where('email', $email)
            ->first();
    }

    public function validatePassword(object $sacratechUser, string $password): bool
    {
        $hash = $sacratechUser->senha ?? $sacratechUser->password_hash ?? null;

        if (! is_string($hash) || $hash === '') {
            return false;
        }

        return password_verify($password, $hash);
    }

    public function isActive(object $sacratechUser): bool
    {
        return (int) ($sacratechUser->status ?? 0) === 1;
    }

    public function requiresTwoFactor(object $sacratechUser): bool
    {
        return (int) ($sacratechUser->dois_fatores_ativo ?? 0) === 1;
    }

    public function validateTwoFactorCode(object $sacratechUser, ?string $code): bool
    {
        if (! $this->requiresTwoFactor($sacratechUser)) {
            return true;
        }

        $code = is_string($code) ? preg_replace('/\D+/', '', $code) : null;

        if (! is_string($code) || $code === '' || strlen($code) < 6) {
            return false;
        }

        $secretColumn = (string) env('SACRATECH_2FA_SECRET_COLUMN', 'segredo_dois_fatores');
        $secret = $sacratechUser->{$secretColumn}
            ?? $sacratechUser->segredo_dois_fatores
            ?? $sacratechUser->dois_fatores_secret
            ?? $sacratechUser->two_factor_secret
            ?? null;

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $secret = trim($secret);

        if (str_starts_with($secret, 'otpauth://')) {
            $url = parse_url($secret);
            $query = is_array($url) ? ($url['query'] ?? null) : null;
            if (is_string($query) && $query !== '') {
                parse_str($query, $params);
                if (isset($params['secret']) && is_string($params['secret']) && $params['secret'] !== '') {
                    $secret = (string) $params['secret'];
                }
            }
        } elseif (str_contains($secret, 'secret=')) {
            if (preg_match('/secret=([A-Z2-7]+)/i', $secret, $m) === 1 && isset($m[1])) {
                $secret = (string) $m[1];
            }
        }

        try {
            $candidates = [];

            $push = function (?string $value) use (&$candidates) {
                if (! is_string($value)) {
                    return;
                }
                $value = trim($value);
                if ($value === '') {
                    return;
                }
                $candidates[] = $value;
            };

            $push($secret);
            $push(Str::replace([' ', '-'], '', $secret));

            $onlyBase32 = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));
            $push($onlyBase32);

            $decoded = base64_decode($secret, true);
            if (is_string($decoded) && $decoded !== '') {
                $push($decoded);
                $push(Str::replace([' ', '-'], '', $decoded));
                $push(strtoupper(preg_replace('/[^A-Z2-7]/i', '', $decoded)));
            }

            $candidates = array_values(array_unique(array_filter($candidates, fn ($v) => is_string($v) && $v !== '')));

            $google2fa = new Google2FA;

            foreach ($candidates as $candidate) {
                $candidate = Str::replace([' ', '-'], '', $candidate);
                if ($candidate === '') {
                    continue;
                }

                try {
                    if ($google2fa->verifyKey($candidate, $code, 4)) {
                        return true;
                    }
                } catch (\Throwable) {
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
