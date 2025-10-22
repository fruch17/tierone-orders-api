<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddStatusCodeToResponse
{
    /**
     * Handle an incoming request.
     * Adds status_code to all JSON responses for consistency
     * Ensures all API responses include the HTTP status code
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only modify JSON responses for API routes
        if ($request->is('api/*') && $response->headers->get('Content-Type') === 'application/json') {
            $content = $response->getContent();
            $data = json_decode($content, true);
            
            // Add status_code if not already present
            if (is_array($data) && !isset($data['status_code'])) {
                $data['status_code'] = $response->getStatusCode();
                $response->setContent(json_encode($data));
            }
        }
        
        return $response;
    }
}
