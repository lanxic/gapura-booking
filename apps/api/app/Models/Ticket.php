<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id', 'qr_code',
        'cloudinary_pdf_id', 'cloudinary_pdf_url',
        'status', 'used_at', 'scanned_by',
    ];

    protected $casts = [
        'status'  => TicketStatus::class,
        'used_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function scannedBy()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function orderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'order_item_tickets')
            ->withPivot('seat_label');
    }
}
