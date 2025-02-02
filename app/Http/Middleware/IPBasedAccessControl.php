<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IPBasedAccessControl
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

     /**
     * A name has to be assigned to the route and added to the ipaccesscontrol config.
     */
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()->getName();
        $clientIP = $request->ip();
        $accessControl = config('ipaccesscontrol'); // Assume this is your config
      
        // Check if there's a specific rule for this route
        if (isset($accessControl[$routeName]) && in_array($clientIP, $accessControl[$routeName])) {
            return $next($request);
            
        }

        return response()->json(['message' => 'Access denied'], 403);
        
    }
}
