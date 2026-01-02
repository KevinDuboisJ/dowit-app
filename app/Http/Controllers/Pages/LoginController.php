<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Contracts\UserAuthenticator;
use App\Models\Edb\Account;
use Illuminate\Support\Facades\Session;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\DeviceUser;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        return Inertia::render('Login', [
            'users' => Inertia::optional(function () use ($request) {
                $search = $request->get('search');

                $query = User::select('username', 'firstname', 'lastname')
                    ->whereNull('object_sid');

                // Only apply filtering when we actually have 2+ characters
                if ($search && strlen($search) >= 2) {
                    $query->where(function ($q) use ($search) {
                            $q->where('firstname', 'like', $search . '%')
                            ->orWhere('lastname', 'like', $search . '%');
                    });
                }

                // Limit returned results
                return $query->limit(20)->get();
            }),
        ]);
    }

    public function authenticate(Request $request, UserAuthenticator $authenticator)
    {
        try {
            $validated = $authenticator->validate($request);
            $authenticator->authenticate($validated['username'], $validated['password']);
        } catch (ModelNotFoundException $e) {
            return Inertia::render('Login', ['errors' => ['wrongCredentials' => 'Er bestaat geen Dowit account voor deze gebruiker']]);
        } catch (Exception $e) {
            return Inertia::render('Login', ['errors' => ['wrongCredentials' => $e->getMessage()]]);
        }

        $this->registerDeviceUse(Auth::user());
        return Inertia::location(Session::get('url.intended', '/'));
    }

    public function registerDeviceUse(User $user)
    {
        // Create a new device usage record
        DeviceUser::create([
            'user_id' => $user->id,
            'hostname' => preg_replace('/\.monica\.be$/', '', gethostbyaddr($_SERVER['REMOTE_ADDR'])),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
