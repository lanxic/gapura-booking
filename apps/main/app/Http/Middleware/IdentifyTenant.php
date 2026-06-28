<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost(); // e.g. "tenantslug.localhost"
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        // Ekstrak subdomain (slug) dari host
        $slug = null;
        if (str_ends_with($host, '.' . $appHost)) {
            $slug = str($host)->before('.' . $appHost)->value();
        } elseif ($host !== $appHost) {
            // Custom domain support
            $tenant = Tenant::where('domain', $host)->where('is_active', true)->first();
            if (! $tenant) {
                abort(404, 'Tenant tidak ditemukan.');
            }
            app()->instance('current_tenant', $tenant);
            View::share('tenant', $tenant);
            URL::defaults(['tenantSlug' => $tenant->slug]);
            return $next($request);
        }

        if (! $slug) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        $tenant = Tenant::where('slug', $slug)->where('is_active', true)->first();

        if (! $tenant) {
            abort(404, 'Tenant tidak ditemukan.');
        }

        app()->instance('current_tenant', $tenant);
        View::share('tenant', $tenant);
        URL::defaults(['tenantSlug' => $tenant->slug]);

        return $next($request);
    }
}
