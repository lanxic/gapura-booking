<?php

namespace App\Providers;

use App\Models\PaymentGateway;
use App\Models\StorageProvider;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->bootMailConfig();
        $this->bootGatewayConfig();
        $this->bootStorageConfig();
    }

    private function bootGatewayConfig(): void
    {
        try {
            $midtrans = Cache::remember('gw_midtrans', 300, fn() => PaymentGateway::where('name', 'midtrans')->first());
            if ($midtrans) {
                $isProd = $midtrans->environment === 'production';
                config([
                    'services.midtrans.server_key'    => $midtrans->server_key ?? '',
                    'services.midtrans.client_key'    => $midtrans->client_key ?? '',
                    'services.midtrans.is_production' => $isProd,
                    'services.midtrans.snap_url'      => $isProd
                        ? 'https://app.midtrans.com/snap/snap.js'
                        : 'https://app.sandbox.midtrans.com/snap/snap.js',
                ]);
            }

            $doku = Cache::remember('gw_doku', 300, fn() => PaymentGateway::where('name', 'doku')->first());
            if ($doku) {
                $dokuIsProd = $doku->environment === 'production';
                config([
                    'services.doku.enabled'       => ! $doku->is_active ? false : true,
                    'services.doku.client_id'     => $doku->merchant_id ?? '',
                    'services.doku.secret_key'    => $doku->server_key  ?? '',
                    'services.doku.is_production' => $dokuIsProd,
                    'services.doku.base_url'      => $dokuIsProd
                        ? 'https://api.doku.com'
                        : 'https://api-sandbox.doku.com',
                ]);
            }
        } catch (\Throwable) {}
    }

    private function bootStorageConfig(): void
    {
        try {
            $provider = Cache::remember('storage_active', 300, fn() => StorageProvider::activeProvider());
            if (! $provider) return;

            $cfg = $provider->config ?? [];

            if ($provider->name === 'aws_s3') {
                config([
                    'filesystems.disks.s3.driver'                  => 's3',
                    'filesystems.disks.s3.key'                     => $cfg['access_key_id']         ?? '',
                    'filesystems.disks.s3.secret'                  => $cfg['secret_access_key']     ?? '',
                    'filesystems.disks.s3.region'                  => $cfg['region']                ?? 'ap-southeast-1',
                    'filesystems.disks.s3.bucket'                  => $cfg['bucket']                ?? '',
                    'filesystems.disks.s3.url'                     => $cfg['cdn_url']               ?? null,
                    'filesystems.disks.s3.use_path_style_endpoint' => (bool) ($cfg['path_style'] ?? false),
                ]);
            }

            config(['services.storage.driver' => $provider->name]);
        } catch (\Throwable) {}
    }

    private function bootMailConfig(): void
    {
        try {
            $cfg = Cache::remember('email_config', 300, fn() => SystemSetting::getGroup('email'));

            if (empty($cfg['host'])) return;

            $scheme = match ($cfg['encryption'] ?? '') {
                'ssl'   => 'smtps',
                'tls'   => 'smtp',
                default => null,
            };

            config([
                'mail.default'               => $cfg['mailer']      ?? 'smtp',
                'mail.mailers.smtp.host'     => $cfg['host'],
                'mail.mailers.smtp.port'     => (int) ($cfg['port'] ?? 587),
                'mail.mailers.smtp.username' => $cfg['smtp_username'] ?? null,
                'mail.mailers.smtp.password' => $cfg['smtp_password'] ?? null,
                'mail.mailers.smtp.scheme'   => $scheme,
                'mail.from.address'          => $cfg['from_address'] ?? 'noreply@example.com',
                'mail.from.name'             => $cfg['from_name']    ?? config('app.name'),
            ]);
        } catch (\Throwable) {}
    }
}
