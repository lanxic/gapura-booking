<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $isTenant   = app()->bound('current_tenant');
        $allowedRoles = $isTenant
            ? ['tenant_admin', 'scanner', 'super_admin']
            : ['super_admin', 'admin'];

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !in_array($user->role->value, $allowedRoles)) {
            return back()->withErrors(['email' => 'Akun admin tidak ditemukan.'])->onlyInput('email');
        }

        if ($isTenant) {
            $tenant = app('current_tenant');
            if (!in_array($user->role->value, ['super_admin']) && $user->tenant_id !== $tenant->id) {
                return back()->withErrors(['email' => 'Akun tidak terdaftar di tenant ini.'])->onlyInput('email');
            }
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Akun Anda tidak aktif.'])->onlyInput('email');
        }

        if (!Auth::guard('admin_session')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $dashboardRoute = $isTenant ? 'tenant.admin.dashboard' : 'admin.dashboard';
        return redirect()->route($dashboardRoute);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin_session')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginRoute = app()->bound('current_tenant') ? 'tenant.admin.login' : 'admin.login';
        return redirect()->route($loginRoute);
    }
}
