<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootMailConfig();
        $this->bootGatewayConfig();
    }

    private function bootGatewayConfig(): void
    {
        try {
            // ── Midtrans ────────────────────────────────────────────────────
            $midtrans = Cache::remember('gateway_midtrans_config', 300, fn() => SiteSetting::getGroup('gateway_midtrans'));
            $isProd   = ($midtrans['environment'] ?? 'sandbox') === 'production';

            config([
                'services.midtrans.server_key'    => $midtrans['server_key'] ?? '',
                'services.midtrans.client_key'    => $midtrans['client_key'] ?? '',
                'services.midtrans.is_production' => $isProd,
                'services.midtrans.snap_url'      => $isProd
                    ? 'https://app.midtrans.com/snap/snap.js'
                    : 'https://app.sandbox.midtrans.com/snap/snap.js',
            ]);

            // ── DOKU ────────────────────────────────────────────────────────
            $doku       = Cache::remember('gateway_doku_config', 300, fn() => SiteSetting::getGroup('gateway_doku'));
            $dokuIsProd = ($doku['environment'] ?? 'sandbox') === 'production';

            config([
                'services.doku.enabled'        => filter_var($doku['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'services.doku.client_id'      => $doku['client_id']  ?? '',
                'services.doku.secret_key'     => $doku['secret_key'] ?? '',
                'services.doku.is_production'  => $dokuIsProd,
                'services.doku.base_url'       => $dokuIsProd
                    ? 'https://api.doku.com'
                    : 'https://api-sandbox.doku.com',
            ]);

            // ── AWS S3 ───────────────────────────────────────────────────────
            $awsCfg = Cache::remember('storage_aws_config', 300, fn() => SiteSetting::getGroup('storage_aws'));

            config([
                'filesystems.disks.s3.driver'                  => 's3',
                'filesystems.disks.s3.key'                     => $awsCfg['access_key_id']     ?? '',
                'filesystems.disks.s3.secret'                  => $awsCfg['secret_access_key'] ?? '',
                'filesystems.disks.s3.region'                  => $awsCfg['region']            ?? 'ap-southeast-1',
                'filesystems.disks.s3.bucket'                  => $awsCfg['bucket']            ?? '',
                'filesystems.disks.s3.url'                     => $awsCfg['cdn_url'] ?: null,
                'filesystems.disks.s3.use_path_style_endpoint' => filter_var($awsCfg['use_path_style_endpoint'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);

            // ── Active storage driver ────────────────────────────────────────
            $storageCfg    = Cache::remember('storage_driver_config', 300, fn() => SiteSetting::getGroup('storage'));
            $activeStorage = $storageCfg['driver'] ?? 'cloudinary';
            config(['services.storage.driver' => $activeStorage]);
        } catch (\Throwable) {
            // DB belum tersedia — pakai env defaults
        }
    }

    private function bootMailConfig(): void
    {
        try {
            $cfg = Cache::remember('email_config', 300, fn() => SiteSetting::getGroup('email'));

            if (empty($cfg['host'])) {
                return;
            }

            $scheme = match ($cfg['encryption'] ?? '') {
                'ssl'  => 'smtps',
                'tls'  => 'smtp',
                default => null,
            };

            config([
                'mail.default'               => $cfg['mailer']       ?? 'smtp',
                'mail.mailers.smtp.host'     => $cfg['host'],
                'mail.mailers.smtp.port'     => (int) ($cfg['port']  ?? 587),
                'mail.mailers.smtp.username' => $cfg['username']      ?? null,
                'mail.mailers.smtp.password' => $cfg['password']      ?? null,
                'mail.mailers.smtp.scheme'   => $scheme,
                'mail.from.address'          => $cfg['from_address']  ?? 'noreply@example.com',
                'mail.from.name'             => $cfg['from_name']     ?? config('app.name'),
            ]);
        } catch (\Throwable) {
            // DB belum tersedia (saat migrate awal) — pakai env defaults
        }
    }
}
