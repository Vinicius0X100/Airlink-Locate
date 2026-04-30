<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(
        private readonly SacratechAuthService $sacratechAuthService,
    ) {}

    public function resolveInviteeByEmail(string $email): User
    {
        $email = mb_strtolower(trim($email));

        $invitee = User::query()->where('email', $email)->first();
        if ($invitee) {
            return $invitee;
        }

        $sacratechUser = $this->sacratechAuthService->fetchUserByEmail($email);
        if (! $sacratechUser) {
            throw ValidationException::withMessages([
                'email' => ['Usuário não encontrado. Crie uma conta Sacratech iD para continuar.'],
            ]);
        }

        if (! $this->sacratechAuthService->isActive($sacratechUser)) {
            throw ValidationException::withMessages([
                'email' => ['Usuário inativo.'],
            ]);
        }

        return User::query()->updateOrCreate(
            ['sacratech_user_id' => (int) $sacratechUser->id],
            [
                'name' => (string) ($sacratechUser->nome ?? $sacratechUser->name ?? ''),
                'email' => (string) ($sacratechUser->email ?? $email),
            ],
        );
    }

    /**
     * @return array{token:string, invitation:Invitation}
     */
    public function createInvitation(array $attrs): array
    {
        do {
            $token = Str::random(64);
            $hash = hash('sha256', $token);
        } while (Invitation::query()->where('token_hash', $hash)->exists());

        $invitation = Invitation::query()->create(array_merge($attrs, [
            'token_hash' => $hash,
        ]));

        return [
            'token' => $token,
            'invitation' => $invitation,
        ];
    }

    public function findByTokenOrFail(string $token): Invitation
    {
        $hash = hash('sha256', $token);

        return Invitation::query()
            ->where('token_hash', $hash)
            ->firstOrFail();
    }
}
