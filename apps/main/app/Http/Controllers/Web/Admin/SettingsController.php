<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // ── General ───────────────────────────────────────────────────────────────
    public function general()
    {
        $settings = SystemSetting::getGroup('general');
        return view('admin.settings.general', compact('settings'));
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name'    => ['nullable', 'string', 'max:255'],
            'site_email'   => ['nullable', 'email', 'max:255'],
            'site_phone'   => ['nullable', 'string', 'max:50'],
            'site_address' => ['nullable', 'string', 'max:500'],
            'logo_url'     => ['nullable', 'url', 'max:1000'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set('general', $key, $value);
        }

        return back()->with('success', 'Pengaturan umum disimpan.');
    }

    // ── Storefront / Hero ─────────────────────────────────────────────────────
    public function storefront()
    {
        $settings = SystemSetting::getGroup('storefront');
        return view('admin.settings.storefront', compact('settings'));
    }

    public function updateStorefront(Request $request)
    {
        $data = $request->validate([
            'hero_image_url' => ['nullable', 'url', 'max:1000'],
            'hero_title'     => ['nullable', 'string', 'max:255'],
            'hero_subtitle'  => ['nullable', 'string', 'max:500'],
            'hero_cta_label' => ['nullable', 'string', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set('storefront', $key, $value);
        }

        return back()->with('success', 'Pengaturan storefront disimpan.');
    }

    // ── Social Media ──────────────────────────────────────────────────────────
    public function social()
    {
        $settings = SystemSetting::getGroup('social');
        return view('admin.settings.social', compact('settings'));
    }

    public function updateSocial(Request $request)
    {
        $data = $request->validate([
            'facebook'  => ['nullable', 'url', 'max:500'],
            'instagram' => ['nullable', 'url', 'max:500'],
            'twitter'   => ['nullable', 'url', 'max:500'],
            'youtube'   => ['nullable', 'url', 'max:500'],
            'whatsapp'  => ['nullable', 'url', 'max:500'],
            'tiktok'    => ['nullable', 'url', 'max:500'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set('social', $key, $value);
        }

        return back()->with('success', 'Pengaturan sosial media disimpan.');
    }

    // ── Payment Gateways ──────────────────────────────────────────────────────
    public function paymentGateways()
    {
        $online  = PaymentGateway::where('type', 'online')->get();
        $offline = PaymentGateway::where('type', 'offline')->get();

        return view('admin.settings.payment-gateways', compact('online', 'offline'));
    }

    public function updateGateway(Request $request, string $name)
    {
        $gateway = PaymentGateway::where('name', $name)->firstOrFail();

        $dualEnvGateways = ['midtrans', 'doku'];

        if (in_array($name, $dualEnvGateways)) {
            $data = $request->validate([
                'environment'            => ['required', 'in:sandbox,production'],
                'sandbox.merchant_id'    => ['nullable', 'string'],
                'sandbox.server_key'     => ['nullable', 'string'],
                'sandbox.client_key'     => ['nullable', 'string'],
                'production.merchant_id' => ['nullable', 'string'],
                'production.server_key'  => ['nullable', 'string'],
                'production.client_key'  => ['nullable', 'string'],
            ]);

            $env    = $data['environment'];
            $config = $gateway->getRawOriginal('config')
                ? json_decode($gateway->getRawOriginal('config'), true)
                : [];

            // Merge each environment's credentials, keeping existing values if not re-entered
            foreach (['sandbox', 'production'] as $e) {
                foreach (['merchant_id', 'server_key', 'client_key'] as $field) {
                    if (!empty($data[$e][$field])) {
                        $config[$e][$field] = $data[$e][$field];
                    }
                }
            }

            // Auto-set gateway base URL in config
            $config['base_url'] = match ($name) {
                'midtrans' => $env === 'production'
                    ? 'https://app.midtrans.com/snap/snap.js'
                    : 'https://app.sandbox.midtrans.com/snap/snap.js',
                'doku' => $env === 'production'
                    ? 'https://api.doku.com'
                    : 'https://api-sandbox.doku.com',
                default => null,
            };

            // Sync active environment's credentials to dedicated encrypted columns
            $active   = $config[$env] ?? [];
            $toUpdate = ['environment' => $env, 'config' => $config];
            if (!empty($active['server_key']))  $toUpdate['server_key']  = $active['server_key'];
            if (!empty($active['client_key']))  $toUpdate['client_key']  = $active['client_key'];
            if (!empty($active['merchant_id'])) $toUpdate['merchant_id'] = $active['merchant_id'];

            $gateway->update($toUpdate);
        } else {
            $gateway->update($request->validate([
                'config' => ['nullable', 'array'],
                'notes'  => ['nullable', 'string'],
            ]));
        }

        return back()->with('success', 'Gateway ' . $name . ' diupdate.');
    }

    public function activateGateway(Request $request, string $name)
    {
        $gateway = PaymentGateway::where('name', $name)->firstOrFail();

        if ($gateway->type === 'online') {
            PaymentGateway::where('type', 'online')->update(['is_active' => false]);
        }

        $gateway->update(['is_active' => !$gateway->is_active]);

        return back()->with('success', 'Status gateway ' . $name . ' diupdate.');
    }

    // ── Legal ─────────────────────────────────────────────────────────────────
    public function legal()
    {
        $settings = SystemSetting::getGroup('legal');
        return view('admin.settings.legal', compact('settings'));
    }

    public function updateLegal(Request $request)
    {
        $data = $request->validate([
            'privacy_policy'   => ['nullable', 'string'],
            'terms_of_service' => ['nullable', 'string'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set('legal', $key, $value);
        }

        return back()->with('success', 'Konten legal disimpan.');
    }
}
