<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPortalAuth
{
    public function handle(Request $request, Closure $next, string $sessionKey = 'customer_portal_account_id'): Response
    {
        if (! $request->session()->has($sessionKey)) {
            $loginRoute = match ($sessionKey) {
                'geiser_customer_portal_account_id' => 'geiser-portal.login',
                'cibena_customer_portal_account_id' => 'cibena-portal.login',
                'school_portal_account_id' => 'school-portal.login',
                default => 'customer-portal.login',
            };

            return redirect()->route($loginRoute)->with('warning', 'Bitte melden Sie sich an.');
        }

        return $next($request);
    }
}
