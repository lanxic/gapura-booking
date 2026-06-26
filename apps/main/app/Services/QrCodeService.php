<?php

namespace App\Services;

use App\Models\StorageProvider;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Generate QR PNG, upload ke active storage, return URL/path.
     */
    public function generateAndUpload(string $token, string $bookingCode): string
    {
        $png = QrCode::format('png')->size(300)->generate($token);

        $filename = "qrcodes/{$bookingCode}.png";
        $provider = StorageProvider::activeProvider();

        if ($provider?->name === 'cloudinary') {
            return $this->uploadToCloudinary($png, $bookingCode, $provider->config ?? []);
        }

        // fallback: simpan di local storage
        \Storage::disk('public')->put($filename, $png);
        return \Storage::disk('public')->url($filename);
    }

    private function uploadToCloudinary(string $png, string $bookingCode, array $config): string
    {
        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => $config['cloud_name'] ?? '',
                'api_key'    => $config['api_key'] ?? '',
                'api_secret' => $config['api_secret'] ?? '',
            ],
        ]);

        $tmpPath = sys_get_temp_dir() . "/{$bookingCode}.png";
        file_put_contents($tmpPath, $png);

        $folder = ($config['folder_prefix'] ?? 'activity-booking') . '/qrcodes';
        $result = $cloudinary->uploadApi()->upload($tmpPath, [
            'public_id' => "{$folder}/{$bookingCode}",
            'resource_type' => 'image',
        ]);

        @unlink($tmpPath);
        return $result['secure_url'] ?? '';
    }
}
