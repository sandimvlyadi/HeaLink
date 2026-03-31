<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check() || ! in_array(auth()->user()->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            abort(403);
        }

        return $next($request);
    }
}
