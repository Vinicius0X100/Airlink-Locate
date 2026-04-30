<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Invitation;
use App\Models\UserConnection;
use App\Services\SacratechAuthService;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $received = Invitation::query()
            ->where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('invitee_user_id', $user->id)
                    ->orWhere('invitee_email', mb_strtolower($user->email));
            })
            ->orderByDesc('id')
            ->get();

        $sent = Invitation::query()
            ->where('inviter_user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'received' => $received,
            'sent' => $sent,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['family', 'circle', 'connection'])],
            'email' => ['nullable', 'email'],
            'family_id' => ['nullable', 'integer', 'min:1'],
            'circle_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $type = (string) $data['type'];
        $email = isset($data['email']) && is_string($data['email']) ? mb_strtolower(trim($data['email'])) : null;

        if (! $email) {
            return response()->json([
                'message' => 'Email é obrigatório para gerar convite.',
                'errors' => ['email' => ['Email é obrigatório para gerar convite.']],
            ], 422);
        }

        $invitee = $this->invitationService->resolveInviteeByEmail($email);

        if ($invitee->id === $user->id) {
            return response()->json([
                'message' => 'Você não pode convidar você mesmo.',
                'errors' => ['email' => ['Você não pode convidar você mesmo.']],
            ], 422);
        }

        $attrs = [
            'type' => $type,
            'inviter_user_id' => $user->id,
            'invitee_user_id' => $invitee->id,
            'invitee_email' => $email,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ];

        if ($type === 'family') {
            $familyId = (int) ($data['family_id'] ?? 0);
            $family = Family::query()->whereKey($familyId)->firstOrFail();

            $isMember = $family->owner_id === $user->id || $family->members()->where('user_id', $user->id)->exists();
            if (! $isMember) {
                abort(403);
            }

            $attrs['family_id'] = $family->id;
        } elseif ($type === 'circle') {
            $circleId = (int) ($data['circle_id'] ?? 0);
            $circle = Circle::query()->whereKey($circleId)->firstOrFail();

            $isMember = $circle->owner_id === $user->id || $circle->members()->where('user_id', $user->id)->exists();
            if (! $isMember) {
                abort(403);
            }

            $attrs['circle_id'] = $circle->id;
        }

        $created = $this->invitationService->createInvitation($attrs);

        return response()->json([
            'invitation' => $created['invitation'],
            'token' => $created['token'],
            'url' => url('/invite/'.$created['token']),
            'expires_at' => $created['invitation']->expires_at?->format(DATE_ATOM),
        ], 201);
    }

    public function show(Request $request, string $token)
    {
        $invitation = $this->invitationService->findByTokenOrFail($token)->load(['inviter', 'family', 'circle']);

        return response()->json([
            'invitation' => $invitation,
            'is_expired' => $invitation->expires_at ? $invitation->expires_at->isPast() : false,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $user = $request->user();

        $invitation = $this->invitationService->findByTokenOrFail($token);

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Convite não está pendente.'], 409);
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            $invitation->update([
                'status' => 'expired',
                'responded_at' => now(),
            ]);

            return response()->json(['message' => 'Convite expirado.'], 410);
        }

        $email = mb_strtolower($user->email);
        if ($invitation->invitee_user_id && $invitation->invitee_user_id !== $user->id) {
            abort(403);
        }
        if ($invitation->invitee_email && mb_strtolower($invitation->invitee_email) !== $email) {
            abort(403);
        }

        if ($invitation->type === 'family') {
            $familyId = (int) $invitation->family_id;
            $family = Family::query()->whereKey($familyId)->firstOrFail();

            FamilyMember::query()->updateOrCreate(
                [
                    'family_id' => $family->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'member',
                ],
            );
        } elseif ($invitation->type === 'circle') {
            $circleId = (int) $invitation->circle_id;
            $circle = Circle::query()->whereKey($circleId)->firstOrFail();

            CircleMember::query()->updateOrCreate(
                [
                    'circle_id' => $circle->id,
                    'user_id' => $user->id,
                ],
                [],
            );
        } else {
            $a = min($invitation->inviter_user_id, $user->id);
            $b = max($invitation->inviter_user_id, $user->id);

            UserConnection::query()->updateOrCreate(
                [
                    'user_a_id' => $a,
                    'user_b_id' => $b,
                ],
                [
                    'requested_by' => (int) $invitation->inviter_user_id,
                    'status' => 'accepted',
                    'share_location' => true,
                    'accepted_at' => now(),
                ],
            );
        }

        $invitation->update([
            'invitee_user_id' => $user->id,
            'invitee_email' => $email,
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        $actorName = (string) ($user->name ?? 'Usuário');
        if ($user->sacratech_user_id) {
            $s = app(SacratechAuthService::class)->fetchUserById((int) $user->sacratech_user_id);
            if ($s) {
                $first = trim((string) ($s->nome ?? ''));
                $last = trim((string) ($s->sobrenome ?? ''));
                $full = trim($first.' '.$last);
                if ($full !== '') {
                    $actorName = $full;
                }
            }
        }

        $parts = array_values(array_filter(preg_split('/\s+/', trim($actorName)) ?: []));
        if (count($parts) >= 2) {
            $initials = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
        } elseif (count($parts) === 1) {
            $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
        } else {
            $initials = '';
        }

        $photoRaw = is_string($user->photo) ? trim((string) $user->photo) : '';
        $photo = null;
        if ($photoRaw !== '') {
            $photo = preg_match('/^https?:\/\//i', $photoRaw) === 1 ? $photoRaw : asset('storage/'.$photoRaw);
        }

        $type = 'connection_accepted';
        $groupId = null;
        $groupName = null;
        $message = 'Aceitou sua conexão.';

        if ($invitation->type === 'family') {
            $type = 'family_accepted';
            $groupId = (int) $invitation->family_id;
            $groupName = $invitation->family?->name ? (string) $invitation->family->name : null;
            $message = 'Aceitou entrar na família'.($groupName ? ' ('.$groupName.')' : '.');
        } elseif ($invitation->type === 'circle') {
            $type = 'circle_accepted';
            $groupId = (int) $invitation->circle_id;
            $groupName = $invitation->circle?->name ? (string) $invitation->circle->name : null;
            $message = 'Aceitou entrar para o círculo'.($groupName ? ' ('.$groupName.')' : '.');
        }

        Alert::query()->create([
            'user_id' => (int) $invitation->inviter_user_id,
            'actor_user_id' => (int) $user->id,
            'type' => $type,
            'message' => $message,
            'actor_name' => $actorName,
            'actor_initials' => $initials,
            'actor_photo' => $photo,
            'group_id' => $groupId,
            'group_name' => $groupName,
            'created_at' => now(),
            'seen_at' => null,
        ]);

        return response()->json([
            'invitation' => $invitation->fresh()->load(['inviter', 'family', 'circle']),
        ]);
    }

    public function decline(Request $request, string $token)
    {
        $user = $request->user();

        $invitation = $this->invitationService->findByTokenOrFail($token);

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Convite não está pendente.'], 409);
        }

        $email = mb_strtolower($user->email);
        if ($invitation->invitee_user_id && $invitation->invitee_user_id !== $user->id) {
            abort(403);
        }
        if ($invitation->invitee_email && mb_strtolower($invitation->invitee_email) !== $email) {
            abort(403);
        }

        $invitation->update([
            'invitee_user_id' => $user->id,
            'invitee_email' => $email,
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        return response()->json([
            'invitation' => $invitation->fresh()->load(['inviter', 'family', 'circle']),
        ]);
    }

    public function revoke(Request $request, Invitation $invitation)
    {
        $user = $request->user();

        if ($invitation->inviter_user_id !== $user->id) {
            abort(403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Convite não está pendente.'], 409);
        }

        $invitation->update([
            'status' => 'revoked',
            'responded_at' => now(),
        ]);

        return response()->json([
            'invitation' => $invitation->fresh(),
        ]);
    }
}
