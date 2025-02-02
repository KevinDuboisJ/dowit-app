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
        // Retrieve Dowit accounts where the associated contract does not have an AD account
        $users = Account::select('an_userid_1 as username')
            ->byDowitAccount()
            ->whereDoesntHave('contract', function ($query) {
                $query->whereHas('accounts', function ($query) {
                    $query->where('an_at_id', 2);
                });
            })
            ->get();

        return Inertia::render('Login', [
            'users' => $users,
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
