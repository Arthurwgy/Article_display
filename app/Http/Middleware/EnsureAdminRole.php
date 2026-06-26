<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 闸控 Filament 后台访问 —— 仅 role = 'admin' 可进入（D23）。
 * 非 admin 已登录用户访问 /admin 返回 403，不重定向。
 */
class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            abort(403);
        }

        return $next($request);
    }
}