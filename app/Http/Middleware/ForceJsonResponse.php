<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes every API request "expect JSON", so validation errors, 401s and 403s
 * are always returned as JSON (never an HTML page or a redirect to a login
 * route) regardless of the Accept header the client sends.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
