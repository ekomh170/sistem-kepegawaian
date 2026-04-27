<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return redirect('/login');
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Please login first',
            ], 401);
        }

        $userRole = Auth::user()->role;

        if (!in_array($userRole, $roles)) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }

            return response()->json([
                'success' => false,
                'message' => 'Forbidden - You do not have permission to access this resource',
            ], 403);
        }

        return $next($request);
    }
}
