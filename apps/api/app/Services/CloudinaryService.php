<?php

namespace App\Services;

use App\Models\StorageProvider;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class CloudinaryService
{
    private function config(): array
    {
        return Cache::remember('cloudinary_config', 300, function () {
            $provider = StorageProvider::where('name', 'cloudinary')->first();
            return $provider?->config ?? [];
        });
    }

    private function client(): Cloudinary
    {
        $cfg = $this->config();

        return new Cloudinary([
            'cloud' => [
                'cloud_name' => $cfg['cloud_name'] ?? '',
                'api_key'    => $cfg['api_key']    ?? '',
                'api_secret' => $cfg['api_secret'] ?? '',
            ],
            'url' => ['secure' => true],
        ]);
    }

    public function uploadImage(UploadedFile $file, string $folder, array $transformations = []): array
    {
        $cfg       = $this->config();
        $maxWidth  = (int) ($cfg['max_width']   ?? 1920);
        $thumbWidth = (int) ($cfg['thumb_width'] ?? 400);
        $quality   = ($cfg['auto_quality'] ?? true) ? 'auto' : null;

        $uploadOptions = [
            'folder'        => $folder,
            'resource_type' => 'image',
            'eager'         => [['width' => $thumbWidth, 'height' => (int) ($thumbWidth * 0.75), 'crop' => 'fill']],
            'eager_async'   => false,
        ];

        if ($maxWidth)  $uploadOptions['width']   = $maxWidth;
        if ($quality)   $uploadOptions['quality'] = $quality;
        if ($cfg['auto_format'] ?? true) $uploadOptions['format'] = 'webp';
        if ($transformations) $uploadOptions['transformation'] = $transformations;

        $result = $this->client()->uploadApi()->upload($file->getRealPath(), $uploadOptions);

        return [
            'public_id'   => $result['public_id'],
            'secure_url'  => $result['secure_url'],
            'eager_url'   => $result['eager'][0]['secure_url'] ?? $result['secure_url'],
        ];
    }

    public function uploadRaw(UploadedFile $file, string $folder): array
    {
        $result = $this->client()->uploadApi()->upload($file->getRealPath(), [
            'folder'        => $folder,
            'resource_type' => 'raw',
        ]);

        return [
            'public_id'  => $result['public_id'],
            'secure_url' => $result['secure_url'],
        ];
    }

    public function deleteAsset(string $publicId, string $resourceType = 'image'): void
    {
        $this->client()->uploadApi()->destroy($publicId, ['resource_type' => $resourceType]);
    }

    public function buildUrl(string $publicId, array $transformations = []): string
    {
        return $this->client()->image($publicId)
            ->toUrl(['transformation' => $transformations]);
    }

    public function testConnection(): bool
    {
        try {
            $this->client()->adminApi()->ping();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getFolders(): array
    {
        $cfg = $this->config();
        return [
            'activities' => $cfg['folder_activities'] ?? 'amartha/activities',
            'bookings'   => $cfg['folder_bookings']   ?? 'amartha/bookings',
            'avatars'    => $cfg['folder_avatars']    ?? 'amartha/avatars',
        ];
    }

    public static function clearConfigCache(): void
    {
        Cache::forget('cloudinary_config');
    }
}
