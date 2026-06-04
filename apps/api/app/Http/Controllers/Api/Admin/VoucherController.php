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
            'code'             => 'nullable|string|max:50|unique:vouchers,code',
            'type'             => 'required|in:percent,fixed',
            'discount_percent' => 'nullable|integer|min:1|max:100',
            'discount_amount'  => 'nullable|integer|min:1',
            'max_discount'     => 'nullable|integer|min:1',
            'min_order'        => 'nullable|integer|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'is_active'        => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $request->all();
        $data['code'] = strtoupper($data['code'] ?? Str::random(8));

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
        $voucher->update($request->only([
            'type', 'discount_percent', 'discount_amount', 'max_discount',
            'min_order', 'max_uses', 'valid_from', 'valid_until', 'is_active',
        ]));

        return response()->json(['data' => $voucher->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        Voucher::findOrFail($id)->delete();
        return response()->json(['message' => 'Voucher dihapus.']);
    }
}
