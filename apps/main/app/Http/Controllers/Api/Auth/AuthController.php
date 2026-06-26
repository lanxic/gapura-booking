<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private function loginWithRole(Request $request, array $allowedRoles): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        if (! in_array($user->role, $allowedRoles)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun tidak aktif.'], 403);
        }

        // Blokir customer yang belum verifikasi email
        if ($user->role === UserRole::Customer && is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Email belum diverifikasi. Silakan cek kotak masuk email Anda.',
                'code'    => 'EMAIL_NOT_VERIFIED',
            ], 403);
        }

        $ttl = (int) env($user->role->jwtTtlEnvKey(), 480);

        JWTAuth::factory()->setTTL($ttl);
        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    public function loginAdmin(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::SuperAdmin, UserRole::Admin]);
    }

    public function loginScanner(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::Scanner]);
    }

    public function loginCustomer(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::Customer]);
    }

    public function registerCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users',
            'phone'                 => 'nullable|string|max:20',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $token = Str::random(64);

        $user = User::create([
            'name'                       => $request->name,
            'email'                      => $request->email,
            'phone'                      => $request->phone,
            'password'                   => $request->password,
            'role'                       => UserRole::Customer,
            'is_active'                  => true,
            'email_verification_token'   => $token,
            'email_verified_at'          => null,
        ]);

        $verifyUrl = url("/v1/auth/customer/verify/{$token}");

        Mail::to($user->email)->send(new VerifyEmailMail($user->name, $verifyUrl));

        return response()->json([
            'message' => 'Pendaftaran berhasil. Silakan cek email Anda untuk verifikasi akun.',
        ], 201);
    }

    /**
     * GET /auth/customer/verify/{token}
     * Dipanggil saat user klik link di email.
     */
    public function verifyEmail(string $token): RedirectResponse
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

        $user = User::where('email_verification_token', $token)
            ->where('role', UserRole::Customer)
            ->first();

        if (! $user) {
            return redirect("{$frontendUrl}/auth/login?verified=invalid");
        }

        if (! is_null($user->email_verified_at)) {
            return redirect("{$frontendUrl}/auth/login?verified=already");
        }

        $user->update([
            'email_verified_at'        => now(),
            'email_verification_token' => null,
        ]);

        return redirect("{$frontendUrl}/auth/login?verified=1");
    }

    /**
     * POST /auth/customer/resend-verification
     * Kirim ulang email verifikasi.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)
            ->where('role', UserRole::Customer)
            ->first();

        // Selalu kembalikan response sukses untuk mencegah email enumeration
        if (! $user || ! is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Jika email terdaftar dan belum terverifikasi, link baru telah dikirim.']);
        }

        $token = Str::random(64);

        $user->update(['email_verification_token' => $token]);

        $verifyUrl = url("/v1/auth/customer/verify/{$token}");

        Mail::to($user->email)->send(new VerifyEmailMail($user->name, $verifyUrl));

        return response()->json(['message' => 'Jika email terdaftar dan belum terverifikasi, link baru telah dikirim.']);
    }

    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return response()->json(['data' => ['token' => $token]]);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(): JsonResponse
    {
        return response()->json(['data' => $this->userPayload(auth('api')->user())]);
    }

    private function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $token,
                'user'  => $this->userPayload($user),
            ],
        ], $status);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role->value,
            'permissions' => $user->getPermissions(),
            'avatarUrl'   => $user->cloudinary_avatar_url,
        ];
    }
}
