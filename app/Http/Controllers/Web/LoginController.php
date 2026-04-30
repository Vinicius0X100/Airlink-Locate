<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SacratechAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(
        private readonly SacratechAuthService $sacratechAuthService,
    ) {}

    public function show()
    {
        if (request()->boolean('reset')) {
            Session::forget('login');
        }

        $stage = (string) Session::get('login.stage', 'email');
        $email = Session::get('login.email');

        if (($stage === 'password' || $stage === '2fa') && ! is_string($email)) {
            Session::forget('login');
            $stage = 'email';
            $email = null;
        }

        return view('auth.login', [
            'stage' => $stage,
            'email' => is_string($email) ? $email : '',
        ]);
    }

    public function authenticate(Request $request)
    {
        $stage = (string) $request->input('stage', Session::get('login.stage', 'email'));

        if ($stage === 'email') {
            $data = $request->validate([
                'email' => ['required', 'email'],
            ]);

            $email = mb_strtolower(trim((string) $data['email']));
            $sacratechUser = $this->sacratechAuthService->fetchUserByEmail($email);

            if (! $sacratechUser) {
                throw ValidationException::withMessages([
                    'email' => ['Conta não encontrada. Crie uma conta Sacratech iD para continuar.'],
                ]);
            }

            if (! $this->sacratechAuthService->isActive($sacratechUser)) {
                throw ValidationException::withMessages([
                    'email' => ['Usuário inativo.'],
                ]);
            }

            Session::put('login.email', $email);
            Session::put('login.stage', 'password');
            Session::forget('login.password_verified_at');

            return redirect()->route('login');
        }

        $email = Session::get('login.email');
        if (! is_string($email) || $email === '') {
            Session::forget('login');

            return redirect()->route('login');
        }

        if ($stage === 'password') {
            $data = $request->validate([
                'password' => ['required', 'string'],
            ]);

            $sacratechUser = $this->sacratechAuthService->fetchUserByEmail($email);
            if (! $sacratechUser || ! $this->sacratechAuthService->validatePassword($sacratechUser, (string) $data['password'])) {
                throw ValidationException::withMessages([
                    'password' => ['Senha incorreta.'],
                ]);
            }

            if (! $this->sacratechAuthService->isActive($sacratechUser)) {
                throw ValidationException::withMessages([
                    'email' => ['Usuário inativo.'],
                ]);
            }

            Session::put('login.password_verified_at', now()->timestamp);

            if ($this->sacratechAuthService->requiresTwoFactor($sacratechUser)) {
                Session::put('login.stage', '2fa');

                return redirect()->route('login');
            }

            return $this->finalizeLogin($request, $sacratechUser, $email);
        }

        if ($stage === '2fa') {
            $data = $request->validate([
                'two_factor_code' => ['required', 'string', 'regex:/^\\d{6,8}$/'],
            ]);

            $verifiedAt = Session::get('login.password_verified_at');
            if (! is_int($verifiedAt) || $verifiedAt < now()->subMinutes(10)->timestamp) {
                Session::forget('login');

                throw ValidationException::withMessages([
                    'email' => ['Sua sessão de login expirou. Tente novamente.'],
                ]);
            }

            $sacratechUser = $this->sacratechAuthService->fetchUserByEmail($email);
            if (! $sacratechUser) {
                Session::forget('login');

                throw ValidationException::withMessages([
                    'email' => ['Conta não encontrada.'],
                ]);
            }

            if (! $this->sacratechAuthService->isActive($sacratechUser)) {
                Session::forget('login');

                throw ValidationException::withMessages([
                    'email' => ['Usuário inativo.'],
                ]);
            }

            if (! $this->sacratechAuthService->validateTwoFactorCode($sacratechUser, (string) $data['two_factor_code'])) {
                throw ValidationException::withMessages([
                    'two_factor_code' => ['Código do app autenticador inválido.'],
                ]);
            }

            return $this->finalizeLogin($request, $sacratechUser, $email);
        }

        Session::forget('login');

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function finalizeLogin(Request $request, object $sacratechUser, string $email)
    {
        $user = User::query()->updateOrCreate(
            ['sacratech_user_id' => (int) $sacratechUser->id],
            [
                'name' => (string) ($sacratechUser->nome ?? $sacratechUser->name ?? ''),
                'email' => (string) ($sacratechUser->email ?? $email),
            ],
        );

        Auth::login($user);
        $request->session()->regenerate();
        Session::forget('login');

        return redirect()->intended('/dashboard');
    }
}
