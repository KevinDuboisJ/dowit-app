<?php

namespace App\Http\Middleware;

use App\Services\DeviceSelectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceSelected
{
    public function __construct(private readonly DeviceSelectionService $deviceSelectionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->deviceSelectionService->isRequiredFor($user)) {
            return $next($request);
        }

        if ($this->deviceSelectionService->hasSelectedDeviceInSession()) {
            return $next($request);
        }

        if ($this->deviceSelectionService->restoreTodaySelection($user)) {
            return $next($request);
        }

        if (! $request->routeIs('device-selection.*')) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('device-selection.index');
        }

        return $next($request);
    }
}
