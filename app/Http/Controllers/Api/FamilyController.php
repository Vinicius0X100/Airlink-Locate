<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\InvitationService;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $families = Family::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->with(['owner', 'members.user'])
            ->get();

        return response()->json([
            'families' => $families,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $user = $request->user();

        $family = Family::query()->create([
            'name' => $data['name'],
            'owner_id' => $user->id,
        ]);

        FamilyMember::query()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        return response()->json([
            'family' => $family->load(['owner', 'members.user']),
        ], 201);
    }

    public function invite(Request $request, Family $family)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $request->user();

        $isMember = $family->owner_id === $user->id || $family->members()->where('user_id', $user->id)->exists();

        if (! $isMember) {
            abort(403);
        }

        $email = mb_strtolower(trim($data['email']));

        $invitee = $this->invitationService->resolveInviteeByEmail($email);

        $created = $this->invitationService->createInvitation([
            'type' => 'family',
            'inviter_user_id' => $user->id,
            'invitee_user_id' => $invitee->id,
            'invitee_email' => $email,
            'family_id' => $family->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'invitation' => $created['invitation'],
            'token' => $created['token'],
            'url' => url('/invite/'.$created['token']),
            'expires_at' => $created['invitation']->expires_at?->format(DATE_ATOM),
        ], 201);
    }
}
