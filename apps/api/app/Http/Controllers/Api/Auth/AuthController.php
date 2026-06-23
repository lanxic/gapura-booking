<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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

        $ttl = (int) env($user->role->jwtTtlEnvKey(), 480);

        JWTAuth::factory()->setTTL($ttl);
        $token = auth('api')->login($user);

        $this->linkGuestOrders($user);

        return $this->respondWithToken($token, $user);
    }

    public function loginAdmin(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::SuperAdmin, UserRole::Admin]);
    }

    public function loginSupervisor(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::Supervisor]);
    }

    public function loginScanner(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::Scanner]);
    }

    public function loginKasir(Request $request): JsonResponse
    {
        return $this->loginWithRole($request, [UserRole::Kasir]);
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
            'password'              => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => UserRole::Customer,
            'is_active' => true,
        ]);

        $ttl = (int) env('JWT_CUSTOMER_TTL', 10080);
        JWTAuth::factory()->setTTL($ttl);
        $token = auth('api')->login($user);

        $this->linkGuestOrders($user);

        return $this->respondWithToken($token, $user, 201);
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

    private function linkGuestOrders(User $user): void
    {
        Order::where('customer_email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
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
