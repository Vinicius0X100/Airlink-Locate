<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafePlace;
use Illuminate\Http\Request;

class SafePlaceController extends Controller
{
    public function index(Request $request)
    {
        $places = SafePlace::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'safe_places' => $places,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:10', 'max:10000'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $place = SafePlace::query()->create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'radius' => (int) $data['radius'],
            'address' => $data['address'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json([
            'safe_place' => $place,
        ], 201);
    }

    public function destroy(Request $request, SafePlace $safePlace)
    {
        if ($safePlace->user_id !== $request->user()->id) {
            abort(403);
        }

        $safePlace->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
