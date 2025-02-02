<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LogWithId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Generate a unique log ID
        $logId = Str::uuid()->toString();

        // Attach the log ID to the logging context
        Log::withContext(['log_id' => $logId]);

        // Add the log ID to the request for later use
        $request->attributes->set('log_id', $logId);

        return $next($request);
    }
}
