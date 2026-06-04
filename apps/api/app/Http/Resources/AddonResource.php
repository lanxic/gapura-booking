<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => (string) $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'maxQty'      => $this->max_qty,
            'isActive'    => $this->is_active,
        ];
    }
}
