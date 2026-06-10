<?php

namespace App\Http\Controllers\Pages;

use App\Enums\EventEnum;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\DeviceSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceSelectionController extends Controller
{
    public function __construct(private readonly DeviceSelectionService $deviceSelectionService)
    {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->deviceSelectionService->isRequiredFor($request->user())) {
            return redirect()->intended(route('dashboard.index'));
        }

        return Inertia::render('SelectDevice', [
            'devices' => Device::query()
                ->registered()
                ->orderBy('identifier')
                ->get(['id', 'identifier', 'description', 'type', 'campus_id']),
            'selectedDeviceId' => session(DeviceSelectionService::SESSION_KEY),
            'isSwitching' => $request->boolean('switch'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'switch' => ['nullable', 'boolean'],
        ]);

        $device = Device::query()
            ->registered()
            ->findOrFail($validated['device_id']);

        $event = $request->boolean('switch')
            ? EventEnum::UserSwitchedDevice
            : EventEnum::UserSelectedDevice;

        $this->deviceSelectionService->selectDevice($request->user(), $device, $event);

        return redirect()->intended(route('dashboard.index'));
    }
}
