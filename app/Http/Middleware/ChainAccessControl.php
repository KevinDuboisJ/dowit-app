<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChainAccessControl
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle(Request $request, Closure $next)
    {
        $chains = \App\Models\Chain::where('trigger_type', 'api')
            ->where('is_active', true)
            ->get();

        $incomingIp = $request->ip();

        // if *any* API‐chain allows this IP, proceed.
        foreach ($chains as $chain) {
            $whitelist = $chain->ip_whitelist ?? [];
            if (in_array($incomingIp, $whitelist, true)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'IP not allowed'], 403);
    }
}
