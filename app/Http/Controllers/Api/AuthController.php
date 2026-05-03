<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SacratechAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly SacratechAuthService $sacratechAuthService,
    ) {}

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string', 'regex:/^\\d{6,8}$/'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        $sacratechUser = $this->sacratechAuthService->fetchUserByEmail($email);

        if (! $sacratechUser || ! $this->sacratechAuthService->validatePassword($sacratechUser, $data['password'])) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $this->sacratechAuthService->isActive($sacratechUser)) {
            throw ValidationException::withMessages([
                'email' => ['Usuário inativo.'],
            ]);
        }

        if ($this->sacratechAuthService->requiresTwoFactor($sacratechUser) && empty($data['two_factor_code'])) {
            throw ValidationException::withMessages([
                'two_factor_code' => ['Informe o código do app autenticador.'],
            ]);
        }

        if (! $this->sacratechAuthService->validateTwoFactorCode($sacratechUser, $data['two_factor_code'] ?? null)) {
            throw ValidationException::withMessages([
                'two_factor_code' => ['Código do app autenticador inválido.'],
            ]);
        }

        $user = User::query()->updateOrCreate(
            ['sacratech_user_id' => (int) $sacratechUser->id],
            [
                'name' => (string) ($sacratechUser->nome ?? $sacratechUser->name ?? ''),
                'email' => (string) ($sacratechUser->email ?? $email),
            ],
        );

        if ($request->hasSession()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'ok' => true,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $path = $data['photo']->storePublicly('user-photos', 'public');

        $user->forceFill([
            'photo' => $path,
        ])->save();

        return response()->json([
            'photo' => $path,
            'url' => asset('storage/'.$path),
        ]);
    }
}
