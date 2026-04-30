<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Invitation;
use App\Models\User;
use App\Models\UserConnection;
use App\Services\SacratechAuthService;
use App\Services\InvitationService;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    private function mismatchView(Request $request, string $token, string $reason = 'Convite inválido para esta conta.')
    {
        $invitation = $this->invitationService->findByTokenOrFail($token)->load(['inviter', 'invitee', 'family', 'circle']);
        $seo = $this->buildSeo($invitation);

        return response()
            ->view('pages.invite', [
                'token' => $token,
                'invitation' => $invitation,
                'mismatch' => true,
                'mismatch_reason' => $reason,
                'expected_email' => $invitation->invitee_email ? mb_strtolower((string) $invitation->invitee_email) : null,
                'current_email' => $request->user()?->email ? mb_strtolower((string) $request->user()->email) : null,
                'seo_title' => $seo['title'],
                'seo_description' => $seo['description'],
                'seo_og_title' => $seo['og_title'],
                'seo_og_description' => $seo['og_description'],
            ], 403);
    }

    private function maskEmail(string $email): string
    {
        $email = trim(mb_strtolower($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = trim($local);
        $domain = trim($domain);

        $localMasked = mb_substr($local, 0, 1).str_repeat('*', max(0, mb_strlen($local) - 1));

        $domainParts = explode('.', $domain);
        $first = $domainParts[0] ?? $domain;
        $rest = count($domainParts) > 1 ? '.'.implode('.', array_slice($domainParts, 1)) : '';
        $firstMasked = mb_substr($first, 0, 1).str_repeat('*', max(0, mb_strlen($first) - 1));

        return $localMasked.'@'.$firstMasked.$rest;
    }

    private function displayName(User $user): string
    {
        $name = (string) ($user->name ?? 'Usuário');
        if ($user->sacratech_user_id) {
            $s = app(SacratechAuthService::class)->fetchUserById((int) $user->sacratech_user_id);
            if ($s) {
                $first = trim((string) ($s->nome ?? ''));
                $last = trim((string) ($s->sobrenome ?? ''));
                $full = trim($first.' '.$last);
                if ($full !== '') {
                    $name = $full;
                }
            }
        }

        return $name;
    }

    /**
     * @return array{title:string,description:string,og_title:string,og_description:string}
     */
    private function buildSeo(Invitation $invitation): array
    {
        $inviter = $invitation->inviter ? $this->displayName($invitation->inviter) : 'Um usuário';

        $invitee = null;
        if ($invitation->invitee instanceof User) {
            $invitee = $this->displayName($invitation->invitee);
        } elseif (is_string($invitation->invitee_email) && trim($invitation->invitee_email) !== '') {
            $invitee = $this->maskEmail((string) $invitation->invitee_email);
        }

        $inviteePart = $invitee ? ' para '.$invitee : '';

        if ($invitation->type === 'family') {
            $familyName = $invitation->family?->name ? (string) $invitation->family->name : 'uma família';
            $title = $inviter.' convidou você para a família '.$familyName.' - Airlink Locate';
            $desc = trim('Olá'.$inviteePart.'! '.$inviter.' convidou você para a família '.$familyName.' no Airlink Locate. Aceite para começar a compartilhar localização com segurança e consentimento.');
            return [
                'title' => $title,
                'description' => $desc,
                'og_title' => $title,
                'og_description' => $desc,
            ];
        }

        if ($invitation->type === 'circle') {
            $circleName = $invitation->circle?->name ? (string) $invitation->circle->name : 'um círculo';
            $title = $inviter.' convidou você para o círculo '.$circleName.' - Airlink Locate';
            $desc = trim('Olá'.$inviteePart.'! '.$inviter.' convidou você para o círculo '.$circleName.' no Airlink Locate. Aceite para acompanhar e ser acompanhado em tempo real, com privacidade e controle.');
            return [
                'title' => $title,
                'description' => $desc,
                'og_title' => $title,
                'og_description' => $desc,
            ];
        }

        $title = $inviter.' quer compartilhar localização com você - Airlink Locate';
        $desc = trim('Olá'.$inviteePart.'! '.$inviter.' convidou você para uma conexão no Airlink Locate. Aceite para compartilhar localização com segurança e poder ver a última localização um do outro.');
        return [
            'title' => $title,
            'description' => $desc,
            'og_title' => $title,
            'og_description' => $desc,
        ];
    }

    private function makeInitials(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return '';
    }

    private function createAcceptedAlert(Request $request, \App\Models\Invitation $invitation): void
    {
        $actor = $request->user();
        if (! $actor) {
            return;
        }

        $actorName = (string) ($actor->name ?? 'Usuário');
        if ($actor->sacratech_user_id) {
            $s = app(SacratechAuthService::class)->fetchUserById((int) $actor->sacratech_user_id);
            if ($s) {
                $first = trim((string) ($s->nome ?? ''));
                $last = trim((string) ($s->sobrenome ?? ''));
                $full = trim($first.' '.$last);
                if ($full !== '') {
                    $actorName = $full;
                }
            }
        }

        $photoRaw = is_string($actor->photo) ? trim((string) $actor->photo) : '';
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
            'actor_user_id' => (int) $actor->id,
            'type' => $type,
            'message' => $message,
            'actor_name' => $actorName,
            'actor_initials' => $this->makeInitials($actorName),
            'actor_photo' => $photo,
            'group_id' => $groupId,
            'group_name' => $groupName,
            'created_at' => now(),
            'seen_at' => null,
        ]);
    }

    public function show(Request $request, string $token)
    {
        $invitation = $this->invitationService->findByTokenOrFail($token)->load(['inviter', 'invitee', 'family', 'circle']);
        $seo = $this->buildSeo($invitation);

        $user = $request->user();
        $email = $user?->email ? mb_strtolower((string) $user->email) : '';
        $expected = $invitation->invitee_email ? mb_strtolower((string) $invitation->invitee_email) : '';
        $mismatch = false;

        if ($user && $invitation->invitee_user_id && (int) $invitation->invitee_user_id !== (int) $user->id) {
            $mismatch = true;
        }
        if ($user && $expected !== '' && $email !== '' && $expected !== $email) {
            $mismatch = true;
        }

        return view('pages.invite', [
            'token' => $token,
            'invitation' => $invitation,
            'mismatch' => $mismatch,
            'mismatch_reason' => $mismatch ? 'Este convite foi gerado para outro email.' : null,
            'expected_email' => $expected !== '' ? $expected : null,
            'current_email' => $email !== '' ? $email : null,
            'seo_title' => $seo['title'],
            'seo_description' => $seo['description'],
            'seo_og_title' => $seo['og_title'],
            'seo_og_description' => $seo['og_description'],
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $user = $request->user();
        $invitation = $this->invitationService->findByTokenOrFail($token);

        if ($invitation->status !== 'pending') {
            return redirect()->route('dashboard');
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            $invitation->update([
                'status' => 'expired',
                'responded_at' => now(),
            ]);

            return redirect()->route('dashboard');
        }

        $email = mb_strtolower($user->email);
        if ($invitation->invitee_user_id && $invitation->invitee_user_id !== $user->id) {
            return $this->mismatchView($request, $token);
        }
        if ($invitation->invitee_email && mb_strtolower($invitation->invitee_email) !== $email) {
            return $this->mismatchView($request, $token);
        }

        if ($invitation->type === 'family') {
            $family = Family::query()->whereKey((int) $invitation->family_id)->firstOrFail();

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
            $circle = Circle::query()->whereKey((int) $invitation->circle_id)->firstOrFail();

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

        $this->createAcceptedAlert($request, $invitation->fresh()->load(['family', 'circle']));

        return redirect()->route('dashboard');
    }

    public function decline(Request $request, string $token)
    {
        $user = $request->user();
        $invitation = $this->invitationService->findByTokenOrFail($token);

        if ($invitation->status !== 'pending') {
            return redirect()->route('dashboard');
        }

        $email = mb_strtolower($user->email);
        if ($invitation->invitee_user_id && $invitation->invitee_user_id !== $user->id) {
            return $this->mismatchView($request, $token);
        }
        if ($invitation->invitee_email && mb_strtolower($invitation->invitee_email) !== $email) {
            return $this->mismatchView($request, $token);
        }

        $invitation->update([
            'invitee_user_id' => $user->id,
            'invitee_email' => $email,
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }
}
