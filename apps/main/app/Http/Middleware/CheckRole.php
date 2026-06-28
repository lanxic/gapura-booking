<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user('admin_session') ?? $request->user('web');

        if (!$user || !in_array($user->role->value, $roles)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
