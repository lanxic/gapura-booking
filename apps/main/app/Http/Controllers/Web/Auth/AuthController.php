<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !in_array($user->role->value, ['customer'])) {
            return back()->withErrors(['email' => 'Akun tidak ditemukan.'])->onlyInput('email');
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Akun Anda tidak aktif.'])->onlyInput('email');
        }

        if (!Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $home = $this->resolveHomeUrl($request) ?? route('login');
        return redirect()->intended($home);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone'    => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'role'      => UserRole::Customer,
            'is_active' => true,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $home = $this->resolveHomeUrl($request) ?? route('login');
        return redirect($home)->with('success', 'Selamat datang, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $home = $this->resolveHomeUrl($request);
        return redirect($home ?? route('login'));
    }

    private function resolveHomeUrl(Request $request): ?string
    {
        // Middleware tenant.identify tidak jalan di auth routes, resolve manual dari host
        if (app()->bound('current_tenant')) {
            return route('tenant.home');
        }

        $host    = $request->getHost();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if ($host !== $appHost && str_ends_with($host, '.' . $appHost)) {
            $slug   = str_replace('.' . $appHost, '', $host);
            $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();
            if ($tenant) {
                return url("http://{$host}/");
            }
        }

        return null;
    }
}
