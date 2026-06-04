<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => (string) $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'location'            => $this->location,
            'openingHours'        => $this->opening_hours,
            'meetingPoint'        => $this->meeting_point,
            'instantConfirmation' => $this->instant_confirmation,
            'highlights'          => $this->highlights ?? [],
            'usageInstructions'   => $this->usage_instructions,
            'cancellationPolicy'  => $this->cancellation_policy,
            'termsConditions'     => $this->terms_conditions,
            'cloudinaryImageUrl'      => $this->cloudinary_image_url,
            'cloudinaryThumbnailUrl'  => $this->cloudinary_thumbnail_url,
            'cloudinaryGalleryUrls'   => $this->cloudinary_gallery_urls ?? [],
            'isActive'            => $this->is_active,
            'sortOrder'           => $this->sort_order,
            'variants'            => ProductVariantResource::collection($this->whenLoaded('variants')),
            'addons'              => AddonResource::collection($this->whenLoaded('addons')),
        ];
    }
}
