<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAdminController extends Controller
{
    private function baseQuery(bool $withTrashed = false): \Illuminate\Database\Eloquent\Builder
    {
        $q = $withTrashed ? User::withTrashed() : User::query();
        return $q->where('role', UserRole::Customer);
    }

    /** GET /admin/customers */
    public function index(Request $request): JsonResponse
    {
        $withTrashed = $request->boolean('trashed');
        $onlyTrashed = $request->boolean('only_trashed');

        $query = User::withTrashed()->where('role', UserRole::Customer);

        if ($onlyTrashed) {
            $query->onlyTrashed();
        } elseif (! $withTrashed) {
            $query->whereNull('deleted_at');
        }

        $customers = $query
            ->when($request->filled('search'), fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name',  'like', '%' . $request->search . '%')
                       ->orWhere('email', 'like', '%' . $request->search . '%')
                       ->orWhere('phone', 'like', '%' . $request->search . '%')))
            ->when($request->filled('verified'), fn ($q) =>
                $request->verified === 'yes'
                    ? $q->whereNotNull('email_verified_at')
                    : $q->whereNull('email_verified_at'))
            ->when($request->filled('active'), fn ($q) =>
                $q->where('is_active', $request->active === 'yes'))
            ->latest()
            ->paginate(20);

        return response()->json($customers);
    }

    /** POST /admin/customers — buat customer baru */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $customer = User::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'phone'            => $request->phone,
            'password'         => $request->password,
            'role'             => UserRole::Customer,
            'is_active'        => true,
            'email_verified_at'=> now(),
        ]);

        return response()->json(['data' => $customer, 'message' => 'Pelanggan berhasil dibuat.'], 201);
    }

    /** GET /admin/customers/{id} */
    public function show(int $id): JsonResponse
    {
        $customer = $this->baseQuery(true)->findOrFail($id);

        $invoices = \App\Models\Invoice::with('slot.activity')
            ->where('guest_email', $customer->email)
            ->latest()->limit(10)->get();

        $bookings = \App\Models\Booking::with('slot.activity')
            ->where('guest_email', $customer->email)
            ->latest()->limit(10)->get();

        return response()->json([
            'data' => array_merge($customer->toArray(), [
                'invoices' => $invoices,
                'bookings' => $bookings,
            ]),
        ]);
    }

    /** PUT /admin/customers/{id} — edit nama, no. HP, status aktif */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = $this->baseQuery(true)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'password'  => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $request->only(['name', 'phone', 'is_active']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $customer->update($data);

        return response()->json(['data' => $customer->fresh(), 'message' => 'Data pelanggan berhasil diperbarui.']);
    }

    /** PATCH /admin/customers/{id}/toggle-active */
    public function toggleActive(int $id): JsonResponse
    {
        $customer = $this->baseQuery()->findOrFail($id);

        $customer->update(['is_active' => ! $customer->is_active]);

        $label = $customer->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json(['data' => $customer->fresh(), 'message' => "Pelanggan berhasil {$label}."]);
    }

    /** DELETE /admin/customers/{id} — soft delete */
    public function destroy(int $id): JsonResponse
    {
        $customer = $this->baseQuery()->findOrFail($id);
        $customer->delete();

        return response()->json(['message' => 'Pelanggan berhasil dihapus.']);
    }

    /** POST /admin/customers/{id}/restore — pulihkan soft delete */
    public function restore(int $id): JsonResponse
    {
        $customer = $this->baseQuery(true)->onlyTrashed()->findOrFail($id);
        $customer->restore();

        return response()->json(['data' => $customer->fresh(), 'message' => 'Pelanggan berhasil dipulihkan.']);
    }

    /** GET /admin/customers/export */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $customers = $this->baseQuery()
            ->when($request->filled('search'), fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name',  'like', '%' . $request->search . '%')
                       ->orWhere('email', 'like', '%' . $request->search . '%')))
            ->when($request->filled('verified'), fn ($q) =>
                $request->verified === 'yes'
                    ? $q->whereNotNull('email_verified_at')
                    : $q->whereNull('email_verified_at'))
            ->when($request->filled('active'), fn ($q) =>
                $q->where('is_active', $request->active === 'yes'))
            ->latest()
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers-' . now()->format('Ymd') . '.csv"',
        ];

        return response()->stream(function () use ($customers) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['ID', 'Nama', 'Email', 'No. HP', 'Email Verified', 'Status', 'Terdaftar']);
            foreach ($customers as $c) {
                fputcsv($fp, [
                    $c->id,
                    $c->name,
                    $c->email,
                    $c->phone ?? '—',
                    $c->email_verified_at ? 'Ya' : 'Belum',
                    $c->is_active ? 'Aktif' : 'Nonaktif',
                    $c->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($fp);
        }, 200, $headers);
    }
}
