<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInternalApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('services.internal_api_token');

        if (empty($token)) {
            abort(503, 'Internal API not configured.');
        }

        $provided = $request->bearerToken();

        if (! $provided || ! hash_equals($token, $provided)) {
            abort(401, 'Unauthorized.');
        }

        return $next($request);
    }
}
