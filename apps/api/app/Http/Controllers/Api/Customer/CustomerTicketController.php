<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['orderItem.product', 'orderItem.variant'])
            ->whereHas('orderItem.order', fn($q) => $q->where('user_id', auth()->id()))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $tickets->items(),
            'meta' => [
                'currentPage' => $tickets->currentPage(),
                'lastPage'    => $tickets->lastPage(),
                'perPage'     => $tickets->perPage(),
                'total'       => $tickets->total(),
            ],
        ]);
    }

    public function download(string $qrCode): JsonResponse
    {
        $ticket = Ticket::where('qr_code', $qrCode)
            ->whereHas('orderItem.order', fn($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();

        if (! $ticket->cloudinary_pdf_url) {
            return response()->json(['message' => 'PDF tiket belum tersedia.'], 404);
        }

        return response()->json([
            'data' => [
                'ticket'  => $ticket,
                'pdf_url' => $ticket->cloudinary_pdf_url,
            ],
        ]);
    }
}
