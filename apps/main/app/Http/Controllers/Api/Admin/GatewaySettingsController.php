<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\PaymentPlan;
use App\Models\StorageProvider;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GatewaySettingsController extends Controller
{
    // ── Payment Gateways ────────────────────────────────────────────────────

    public function gateways(): JsonResponse
    {
        $all = PaymentGateway::all();

        $online  = $all->where('type', 'online')->map(fn ($g) => [
            'id'             => $g->id,
            'name'           => $g->name,
            'type'           => 'online',
            'is_active'      => $g->is_active,
            'environment'    => $g->environment,
            'has_server_key' => ! empty($g->getRawOriginal('server_key')),
            'has_client_key' => ! empty($g->getRawOriginal('client_key')),
        ])->values();

        $offline = $all->where('type', 'offline')->map(fn ($g) => [
            'id'        => $g->id,
            'name'      => $g->name,
            'type'       => 'offline',
            'is_active' => $g->is_active,
            'notes'     => $g->notes,
            'config'    => $g->config,
        ])->values();

        return response()->json(['data' => ['online' => $online, 'offline' => $offline]]);
    }

    public function updateGateway(Request $request, string $name): JsonResponse
    {
        $gateway = PaymentGateway::where('name', $name)->firstOrFail();

        if ($gateway->type === 'offline') {
            $data = $request->validate([
                'is_active' => 'nullable|boolean',
                'notes'     => 'nullable|string|max:500',
                'config'    => 'nullable|array',
            ]);
        } else {
            $data = $request->validate([
                'server_key'  => 'nullable|string',
                'client_key'  => 'nullable|string',
                'merchant_id' => 'nullable|string',
                'environment' => 'nullable|in:sandbox,production',
                'config'      => 'nullable|array',
            ]);
        }

        $gateway->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['message' => "Gateway {$name} diperbarui."]);
    }

    /**
     * Toggle offline method (cash / bank_transfer) — bebas aktif bersamaan.
     * Online gateway: hanya 1 aktif sekaligus (PRD Section 4.4.3).
     */
    public function activateGateway(Request $request, string $name): JsonResponse
    {
        $gateway = PaymentGateway::where('name', $name)->firstOrFail();

        if ($gateway->type === 'offline') {
            // Offline: simple toggle, boleh aktif bersamaan
            $gateway->update(['is_active' => ! $gateway->is_active]);
            $state = $gateway->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan';
            return response()->json(['message' => "Metode {$name} {$state}."]);
        }

        // Online: toggle — jika sudah aktif, nonaktifkan saja
        if ($gateway->is_active) {
            $gateway->update(['is_active' => false]);
            return response()->json(['message' => "Gateway {$name} dinonaktifkan."]);
        }

        // Aktifkan — nonaktifkan yang lain dulu (constraint: maks 1 aktif)
        $currentActive = PaymentGateway::where('type', 'online')->where('is_active', true)->first();
        $pendingCount  = 0;
        if ($currentActive) {
            $pendingCount = Invoice::where('gateway', $currentActive->name)
                ->where('status', 'pending')
                ->count();
        }

        DB::transaction(function () use ($gateway) {
            PaymentGateway::where('type', 'online')->update(['is_active' => false]);
            $gateway->update(['is_active' => true]);
        });

        $message = "Gateway {$name} diaktifkan.";
        if ($pendingCount > 0) {
            $message .= " {$pendingCount} invoice pending di gateway lama masih diproses hingga settled.";
        }

        return response()->json(['message' => $message, 'pending_count' => $pendingCount]);
    }

    // ── Payment Plans ────────────────────────────────────────────────────────

    public function paymentPlans(): JsonResponse
    {
        return response()->json(['data' => PaymentPlan::all()]);
    }

    public function updatePaymentPlan(Request $request, string $code): JsonResponse
    {
        if ($code === 'FULL') {
            return response()->json(['message' => 'FULL payment tidak dapat dinonaktifkan.'], 422);
        }

        $plan = PaymentPlan::where('code', $code)->firstOrFail();
        $data = $request->validate([
            'is_active'      => 'sometimes|boolean',
            'min_amount'     => 'sometimes|integer|min:0',
            'deadline_hours' => 'sometimes|integer|min:1',
        ]);

        $plan->update($data);

        return response()->json(['data' => $plan->fresh()]);
    }

    // ── Storage Providers ────────────────────────────────────────────────────

    public function storageProviders(): JsonResponse
    {
        $providers = StorageProvider::all()->map(fn ($p) => [
            'id'             => $p->id,
            'name'           => $p->name,
            'is_active'      => $p->is_active,
            'max_file_size'  => $p->max_file_size,
            'allowed_formats'=> $p->allowed_formats,
            'folder_prefix'  => $p->folder_prefix,
            'has_config'     => ! empty($p->getRawOriginal('config')),
        ]);

        return response()->json(['data' => $providers]);
    }

    public function activateStorageProvider(string $name): JsonResponse
    {
        $provider = StorageProvider::where('name', $name)->firstOrFail();

        DB::transaction(function () use ($provider) {
            StorageProvider::query()->update(['is_active' => false]);
            $provider->update(['is_active' => true]);
        });

        return response()->json(['message' => "Storage provider {$name} diaktifkan."]);
    }

    // ── Storage Provider — update with config merge ────────────────────────────

    public function updateStorageProvider(Request $request, string $name): JsonResponse
    {
        $provider = StorageProvider::where('name', $name)->firstOrFail();
        $data     = $request->validate([
            'config'          => 'nullable|array',
            'max_file_size'   => 'nullable|integer|min:1',
            'allowed_formats' => 'nullable|array',
            'folder_prefix'   => 'nullable|string|max:100',
        ]);

        // Merge config: keep existing values, only overwrite non-empty incoming values
        if (! empty($data['config'])) {
            $existing       = $provider->config ?? [];
            $incoming       = array_filter($data['config'], fn ($v) => $v !== '' && $v !== null);
            $data['config'] = array_merge($existing, $incoming);
        }

        $provider->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['message' => "Storage provider {$name} diperbarui."]);
    }

    // ── System Settings ───────────────────────────────────────────────────────

    // Sensitive field names per group → key stored in db, masked in GET response
    private array $sensitiveMap = [
        'email'      => ['password'           => 'password_configured'],
        'cloudinary' => ['api_key'            => 'api_key_configured',
                         'api_secret'         => 'api_secret_configured'],
        'aws'        => ['access_key_id'      => 'access_key_configured',
                         'secret_access_key'  => 'secret_key_configured'],
    ];

    private array $allowedGroups = [
        'email', 'whatsapp', 'booking', 'general', 'legal',
        'notifications', 'hero', 'cloudinary', 'aws',
    ];

    private array $encryptedKeys = [
        'smtp_username', 'smtp_password', 'auth_token', 'account_sid',
        'password', 'api_key', 'api_secret',
        'access_key_id', 'secret_access_key',
    ];

    public function getSystemSettings(string $group): JsonResponse
    {
        if (! in_array($group, $this->allowedGroups)) {
            return response()->json(['message' => 'Group tidak dikenal.'], 422);
        }

        $data = SystemSetting::getGroup($group);

        // Mask sensitive fields per group
        foreach (($this->sensitiveMap[$group] ?? []) as $field => $indicator) {
            $data[$indicator] = ! empty($data[$field]);
            unset($data[$field]);
        }

        return response()->json(['data' => $data]);
    }

    public function updateSystemSettings(Request $request, string $group): JsonResponse
    {
        if (! in_array($group, $this->allowedGroups)) {
            return response()->json(['message' => 'Group tidak dikenal.'], 422);
        }

        foreach ($request->all() as $key => $value) {
            $isEncrypted = in_array($key, $this->encryptedKeys);
            SystemSetting::set($group, $key, $value, $isEncrypted);
        }

        return response()->json(['message' => "Settings group '{$group}' diperbarui."]);
    }

    // ── Email test ────────────────────────────────────────────────────────────

    public function testEmail(Request $request): JsonResponse
    {
        $data = $request->validate(['to' => 'required|email']);
        $s    = SystemSetting::getGroup('email');

        if (empty($s['host'])) {
            return response()->json(['data' => ['sent' => false, 'error' => 'Host SMTP belum dikonfigurasi.']]);
        }

        try {
            $scheme = ($s['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp';
            $creds  = '';
            if (! empty($s['username']) && ! empty($s['password'])) {
                $creds = rawurlencode($s['username']) . ':' . rawurlencode($s['password']) . '@';
            }
            $port = (int) ($s['port'] ?? 587);
            $dsn  = "{$scheme}://{$creds}{$s['host']}:{$port}";

            $transport = \Symfony\Component\Mailer\Transport::fromDsn($dsn);
            $mailer    = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address(
                    $s['from_address'] ?? 'noreply@localhost',
                    $s['from_name']    ?? config('app.name'),
                ))
                ->to($data['to'])
                ->subject('[Test] Email ' . config('app.name'))
                ->text('Ini adalah email percobaan. Konfigurasi SMTP berfungsi dengan baik.');

            $mailer->send($email);

            return response()->json(['data' => ['sent' => true]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['sent' => false, 'error' => $e->getMessage()]]);
        }
    }

    // ── Cloudinary settings test ──────────────────────────────────────────────

    public function testCloudinarySettings(): JsonResponse
    {
        $config  = SystemSetting::getGroup('cloudinary');
        $missing = array_filter(['cloud_name', 'api_key', 'api_secret'], fn ($k) => empty($config[$k]));

        if (! empty($missing)) {
            return response()->json(['data' => ['connected' => false, 'error' => 'Field wajib belum diisi: ' . implode(', ', $missing)]]);
        }

        return response()->json(['data' => ['connected' => true]]);
    }

    // ── AWS settings test ─────────────────────────────────────────────────────

    public function testAwsSettings(): JsonResponse
    {
        $config  = SystemSetting::getGroup('aws');
        $missing = array_filter(['access_key_id', 'secret_access_key', 'region', 'bucket'], fn ($k) => empty($config[$k]));

        if (! empty($missing)) {
            return response()->json(['data' => ['connected' => false, 'error' => 'Field wajib belum diisi: ' . implode(', ', $missing)]]);
        }

        return response()->json(['data' => ['connected' => true]]);
    }
}
