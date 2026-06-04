<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilitySlotResource extends JsonResource
{
    public function toArray($request): array
    {
        $remaining = $this->total_quota - $this->booked_qty;

        if ($this->is_blocked) {
            $status = 'blocked';
        } elseif ($remaining <= 0) {
            $status = 'full';
        } elseif ($remaining <= 5) {
            $status = 'limited';
        } else {
            $status = 'available';
        }

        return [
            'id'         => (string) $this->id,
            'productId'  => (string) $this->product_id,
            'date'       => $this->date->format('Y-m-d'),
            'timeSlot'   => $this->time_slot,
            'totalQuota' => $this->total_quota,
            'bookedQty'  => $this->booked_qty,
            'isBlocked'  => $this->is_blocked,
            'remaining'  => max(0, $remaining),
            'status'     => $status,
        ];
    }
}
