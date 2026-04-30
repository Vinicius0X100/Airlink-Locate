<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Services\InvitationService;
use Illuminate\Http\Request;

class CircleController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $circles = Circle::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->with(['owner', 'members.user'])
            ->get();

        return response()->json([
            'circles' => $circles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $user = $request->user();

        $circle = Circle::query()->create([
            'name' => $data['name'],
            'owner_id' => $user->id,
        ]);

        CircleMember::query()->create([
            'circle_id' => $circle->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'circle' => $circle->load(['owner', 'members.user']),
        ], 201);
    }

    public function addMember(Request $request, Circle $circle)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $request->user();

        $isMember = $circle->owner_id === $user->id || $circle->members()->where('user_id', $user->id)->exists();

        if (! $isMember) {
            abort(403);
        }

        $email = mb_strtolower(trim($data['email']));

        $invitee = $this->invitationService->resolveInviteeByEmail($email);

        $created = $this->invitationService->createInvitation([
            'type' => 'circle',
            'inviter_user_id' => $user->id,
            'invitee_user_id' => $invitee->id,
            'invitee_email' => $email,
            'circle_id' => $circle->id,
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
