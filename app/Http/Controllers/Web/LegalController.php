<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function terms()
    {
        return view('pages.termos');
    }

    public function privacy()
    {
        return view('pages.privacidade');
    }

    public function accept(Request $request)
    {
        $user = $request->user();

        $user->forceFill([
            'airlink_locate_fisrt_entire' => true,
        ])->save();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function locationRequired()
    {
        return view('pages.localizacao-necessaria');
    }
}
