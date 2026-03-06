<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->isGoogleAuthConfigured()) {
            return redirect('/login')->with('google_auth_error', 'Login com Google não configurado. Defina as variáveis GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET e GOOGLE_REDIRECT_URI.');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isGoogleAuthConfigured()) {
            return redirect('/login')->with('google_auth_error', 'Login com Google não configurado.');
        }

        $state = (string) $request->input('state', '');
        $savedState = (string) $request->session()->pull('google_oauth_state', '');

        if ($state === '' || $savedState === '' || ! hash_equals($savedState, $state)) {
            return redirect('/login')->with('google_auth_error', 'Falha de segurança no login com Google. Tente novamente.');
        }

        if ($request->filled('error')) {
            return redirect('/login')->with('google_auth_error', 'Login com Google cancelado.');
        }

        $code = (string) $request->input('code', '');

        if ($code === '') {
            return redirect('/login')->with('google_auth_error', 'Código de autorização inválido.');
        }

        try {
            $tokenResponse = Http::asForm()
                ->timeout(20)
                ->post('https://oauth2.googleapis.com/token', [
                    'code' => $code,
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'redirect_uri' => config('services.google.redirect'),
                    'grant_type' => 'authorization_code',
                ]);

            if (! $tokenResponse->ok()) {
                return redirect('/login')->with('google_auth_error', 'Não foi possível autenticar no Google.');
            }

            $accessToken = (string) $tokenResponse->json('access_token');

            if ($accessToken === '') {
                return redirect('/login')->with('google_auth_error', 'Token de acesso inválido.');
            }

            $userInfoResponse = Http::timeout(20)
                ->withToken($accessToken)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');

            if (! $userInfoResponse->ok()) {
                return redirect('/login')->with('google_auth_error', 'Não foi possível obter dados do usuário Google.');
            }

            $googleId = (string) $userInfoResponse->json('sub', '');
            $email = (string) $userInfoResponse->json('email', '');
            $name = (string) $userInfoResponse->json('name', '');
            $avatar = (string) $userInfoResponse->json('picture', '');

            if ($googleId === '' || $email === '') {
                return redirect('/login')->with('google_auth_error', 'Google não retornou dados suficientes para login.');
            }

            $user = User::query()
                ->where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => $name !== '' ? $name : Str::before($email, '@'),
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'google_id' => $googleId,
                    'google_avatar' => $avatar !== '' ? $avatar : null,
                    'email_verified_at' => now(),
                ]);
            } else {
                $updates = [];

                if (! $user->google_id) {
                    $updates['google_id'] = $googleId;
                }

                if (! $user->google_avatar && $avatar !== '') {
                    $updates['google_avatar'] = $avatar;
                }

                if (! $user->email_verified_at) {
                    $updates['email_verified_at'] = now();
                }

                if ($updates !== []) {
                    $user->update($updates);
                }
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect('/');
        } catch (Throwable) {
            return redirect('/login')->with('google_auth_error', 'Erro inesperado no login com Google.');
        }
    }

    private function isGoogleAuthConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
