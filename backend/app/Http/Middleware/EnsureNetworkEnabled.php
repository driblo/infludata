<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Network;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses requests touching a network that is disabled by feature flag.
 *
 * Looks for the `network` field in the validated input or as a route param.
 */
class EnsureNetworkEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $network = (string) ($request->input('network') ?? $request->route('network') ?? '');
        if ($network !== '' && ! Network::isEnabled($network)) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'NetworkDisabled',
                'status' => 422,
                'detail' => "Network {$network} is disabled in this environment.",
            ], 422);
        }

        return $next($request);
    }
}
