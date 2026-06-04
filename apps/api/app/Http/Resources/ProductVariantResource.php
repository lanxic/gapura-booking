<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => (string) $this->id,
            'productId'   => (string) $this->product_id,
            'label'       => $this->label,
            'description' => $this->description,
            'priceAdult'  => $this->price_adult,
            'priceChild'  => $this->price_child,
            'minQty'      => $this->min_qty,
            'maxQty'      => $this->max_qty,
            'adultMinAge' => $this->adult_min_age ?? 3,
            'adultMaxAge' => $this->adult_max_age ?? 99,
            'childMinAge' => $this->child_min_age ?? 3,
            'childMaxAge' => $this->child_max_age ?? 12,
            'isActive'    => $this->is_active,
        ];
    }
}
