<?php

namespace App\Http\Middleware;

use App\Models\VendorPortalToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorPortalAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tokenValue = session('vendor_portal_token');

        if (! $tokenValue) {
            return redirect()->route('vendor.login')
                ->with('error', 'Please log in to access the vendor portal.');
        }

        $portalToken = VendorPortalToken::where('token', $tokenValue)
            ->valid()
            ->with('vendor')
            ->first();

        if (! $portalToken || ! $portalToken->isValid()) {
            session()->forget('vendor_portal_token');
            return redirect()->route('vendor.login')
                ->with('error', 'Your session has expired. Please log in again.');
        }

        // Set vendor on the request for controllers to use
        $request->merge(['vendor' => $portalToken->vendor]);
        $request->attributes->set('vendor', $portalToken->vendor);

        return $next($request);
    }
}
