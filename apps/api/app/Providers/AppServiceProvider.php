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

            $scheme = match ($cfg['encryption'] ?? 'tls') {
                'ssl'  => 'ssl',
                'tls'  => 'tls',
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
