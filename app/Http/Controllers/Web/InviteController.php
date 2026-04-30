<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\UserConnection;
use App\Services\InvitationService;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function show(Request $request, string $token)
    {
        $invitation = $this->invitationService->findByTokenOrFail($token)->load(['inviter', 'family', 'circle']);

        return view('pages.invite', [
            'token' => $token,
            'invitation' => $invitation,
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
            abort(403);
        }
        if ($invitation->invitee_email && mb_strtolower($invitation->invitee_email) !== $email) {
            abort(403);
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

        return redirect()->route('dashboard');
    }
}
