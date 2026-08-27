<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FavoritesService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request, FavoritesService $favorites)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.mb_strtolower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('email'))
                ->with('error', __('shop.too_many_attempts', ['seconds' => RateLimiter::availableIn($key)]));
        }

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($key, 900);

            return back()->withInput($request->only('email'))->with('error', __('shop.invalid_credentials'));
        }

        if (! auth()->user()->is_active) {
            Auth::logout();

            return back()->with('error', __('shop.account_disabled'));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $favorites->attachToUser(auth()->id());
        auth()->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(localized_route('account.index'));
    }

    public function showRegister()
    {
        return view('pages.auth.register');
    }

    public function register(Request $request, FavoritesService $favorites)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'terms' => ['accepted'],
            'accepts_marketing' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => User::ROLE_CUSTOMER,
            'locale' => app()->getLocale(),
            'accepts_marketing' => (bool) ($data['accepts_marketing'] ?? false),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();
        $favorites->attachToUser($user->id);

        return redirect(localized_route('account.index'))->with('status', __('shop.welcome'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(localized_route('home'));
    }

    // ------------------------------------------------------------ sifre sifirlama

    public function showForgot()
    {
        return view('pages.auth.forgot');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return back()->with('status', __('shop.reset_link_sent'));
    }

    public function showReset(string $token, Request $request)
    {
        return view('pages.auth.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect(localized_route('login'))->with('status', __('shop.password_reset_done'));
        }

        return back()->with('error', __('shop.password_reset_failed'));
    }
}
