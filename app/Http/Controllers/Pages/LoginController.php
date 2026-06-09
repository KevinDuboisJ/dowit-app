<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Contracts\UserAuthenticator;
use App\Enums\EventEnum;
use App\Models\Comment;
use App\Models\Device;
use Illuminate\Support\Facades\Session;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        return Inertia::render('Login', [
            'users' => Inertia::optional(function () use ($request) {
                $search = $request->get('search');

                $query = User::select('username', 'firstname', 'lastname')
                    ->whereNull('object_sid')
                    ->where('is_active', 1);

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
            throw ValidationException::withMessages([
                'wrongCredentials' => 'Er bestaat geen Dowit account voor deze gebruiker',
            ]);
        } catch (Exception $e) {
            $errors = method_exists($e, 'errors') ? $e->errors() : null;
            throw ValidationException::withMessages([
                'wrongCredentials' => $errors ? [collect($errors)->flatten()->first()] : $e->getMessage(),
            ]);
        }

        return Inertia::location(Session::get('url.intended', '/'));
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        $user->setLastLogout();

        $user->save();

        Comment::create([
            'created_by' => $user->id,
            'event' => EventEnum::UserLoggedOut->value,
            'content' => 'Gebruiker heeft zich uitgelogd',
        ]);

        $device = Device::resolveFromHostname();
        $device->setLastUsed($user)->save();
        $device->logUserUsage($user, EventEnum::UserLoggedOut);

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
