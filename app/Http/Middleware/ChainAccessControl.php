<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Chain;

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
        $endpoint = collect(explode('/', $request->path()))->last();

        $chains = Chain::where('trigger_type', 'api')
            ->where('identifier', $endpoint)
            ->where('is_active', true)
            ->get();

        // if *any* API‐chain allows this IP, proceed.
        foreach ($chains as $chain) {
            $whitelist = $chain->ip_whitelist ?? [];
            if (in_array($request->ip(), $whitelist, true)) {
                return $next($request);
            }
        }

        if ($chains->isEmpty()) {
            return response()->json(['error' => 'No active chain found for this endpoint'], 404);
        }

        logger('Blocked API request', [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'payload' => $request->all(),
        ]);

        return response()->json(['error' => 'IP not allowed'], 403);
    }
}
