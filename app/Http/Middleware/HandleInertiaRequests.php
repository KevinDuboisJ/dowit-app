<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Services\DeviceSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';


    public function __construct(
        private DeviceSelectionService $deviceSelectionService
    ) {}


    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        $user = Auth::user()?->loadMissing('teams:id,name');

        if ($user) {
            $userResource = new UserResource($user);
            $userResource->append([
                'devices' => [
                    'cleaningCart' => $this->deviceSelectionService->getLastCleaningCartSelection($user),
                ],
            ]);
            $user = $userResource->resolve();
        }

        return array_merge(parent::share($request), [
            'user' => $user,
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
            ],
        ]);
    }
}
