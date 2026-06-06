<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vouchers = Voucher::when($request->search, fn($q, $s) => $q->where('code', 'like', "%$s%"))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $vouchers->items(),
            'meta' => [
                'currentPage' => $vouchers->currentPage(),
                'lastPage'    => $vouchers->lastPage(),
                'perPage'     => $vouchers->perPage(),
                'total'       => $vouchers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'         => 'nullable|string|max:50|unique:vouchers,code',
            'type'         => 'required|in:percent,fixed',
            'value'        => 'required|integer|min:1',
            'min_purchase' => 'nullable|integer|min:0',
            'quota'        => 'nullable|integer|min:0',
            'valid_from'   => 'nullable|date',
            'valid_until'  => 'nullable|date|after_or_equal:valid_from',
            'is_active'    => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $request->all();
        $data['code']         = strtoupper($data['code'] ?? Str::random(8));
        $data['min_purchase'] = $data['min_purchase'] ?? 0;
        $data['quota']        = $data['quota']        ?? 0;

        $voucher = Voucher::create($data);

        return response()->json(['data' => $voucher], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Voucher::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $voucher = Voucher::findOrFail($id);
        $fields = $request->only([
            'type', 'value', 'min_purchase', 'quota',
            'valid_from', 'valid_until', 'is_active',
        ]);
        if (array_key_exists('min_purchase', $fields)) $fields['min_purchase'] ??= 0;
        if (array_key_exists('quota',        $fields)) $fields['quota']        ??= 0;

        $voucher->update($fields);

        return response()->json(['data' => $voucher->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        Voucher::findOrFail($id)->delete();
        return response()->json(['message' => 'Voucher dihapus.']);
    }
}
