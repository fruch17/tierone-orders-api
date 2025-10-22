<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     * Ensures only admin users can access protected routes
     * Following Authorization best practices (Security)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status_code' => 401,
            ], 401);
        }

        // Check if user has admin role
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'message' => 'Forbidden. Admin access required.',
                'error' => 'You must be an admin to access this resource',
                'status_code' => 403,
            ], 403);
        }

        return $next($request);
    }
}