<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use App\Models\Team;
use App\Contracts\Teams;
use App\Traits\HasTeams;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Scopes\ExcludeSystemUser;

class User extends Authenticatable implements FilamentUser, HasName, HasAvatar, Teams
{
    use HasFactory, Notifiable, HasTeams;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */

    protected $hidden = [
        'password',
        'object_sid',
        'is_active',
        'updated_at',
        'last_login',
        'edb_id',
        'department_id',
        'created_at'
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'password' => 'hashed',
        'last_login' => 'datetime',
    ];

    protected $appends = [
        'roles',
        'permissions'
    ];

    public function getFullNameAttribute()
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function scopeExcludeSystemUser($query)
    {
        return $query->where('id', '!=', config('app.system_user_id'));
    }

    public function scopeByUserTeams(Builder $query): Builder
    {
        // Get authenticated user, $this is not available at the moment of this method
        $user = Auth::user();

        // Get all team IDs the user belongs to
        $teamIds = $user->teams->pluck('id')->toArray();

        if (!empty($teamIds)) {

            $query->whereHas('teams', function ($query) use ($teamIds) {
                $query->whereIn('team_id', $teamIds);
            });

            return $query;
        }

        throw new AuthenticationException('Gebruiker heeft geen teams');
    }

    public function getFormattedDepartmentIdAttribute()
    {
        return sprintf('DEPT%03d', $this->department_id);
    }

    public function getFormattedProfessionIdAttribute()
    {
        return sprintf('PROF%03d', $this->profession_id);
    }

    public function setLastLogin()
    {
        $this->last_login = Carbon::now()->format('Y-m-d H:i:s');
        return $this;
    }

    protected function roles(): Attribute
    {
        return Attribute::make(
            get: fn() => session()->get('roles', []),
            set: fn(array $value) => session(['roles' => $value]),
        );
    }

    public function hasRole(String|array $roles): bool
    {
        return !empty(array_intersect($this->roles, Arr::wrap($roles)));
    }

    public function getImagePathAttribute()
    {
        // A default dummy profile image is used as a fallback when no image path is provided during user creation. 
        // This typically occurs when a user is created from an external source, rather than directly through the app.
        $imagePath = $this->attributes['image_path'] ?? 'dummy-profile.png';
        return "https://edb.monica.be/uploads/photos/$imagePath";
    }

    public function getPermissionsAttribute()
    {
        return [
            'seeAdminMenu' => Gate::allows('seeAdminMenu', [$this->roles]),
            'seeItems' => Gate::allows('seeItems', [$this->roles]),
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->image_path;
    }

    public function getFilamentName(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!empty(array_intersect([1, 2], array_flip($this->roles)))) {
            return true;
        }

        return false;
    }

    static function getProfileImage($username): string
    {
        $result = DB::connection('edb')
            ->table('account')
            ->select('person.pe_photo_front')
            ->leftJoin('contract', 'account.an_cn_id', '=', 'contract.cn_id')
            ->leftJoin('person', 'contract.cn_pe_id', '=', 'person.pe_id')
            ->where('an_userid_1', '=', $username)
            ->where('an_at_id', '=', '2')
            ->value('person.pe_photo_front');

        return empty($result) ? 'dummy-profile.png' : $result;
    }

    private function getTeamsByGroups(array $entries): array
    {
        $teams = collect($entries)
            ->map(fn($value) => explode(',', $value))
            ->flatMap(function ($group) {
                return Team::query()
                    ->whereRaw('json_contains(groups, \'["' . $group . '"]\')')
                    ->get()
                    ->pluck('groups')
                    ->flatten();
            })
            ->pluck('name', 'id')
            ->toArray();

        return $teams;
    }

    public function getTeamIds(): array
    {
        return $this->teams->pluck('id')->toArray();
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('SUPER_ADMIN');
    }

    public function isAdmin()
    {
        return $this->hasRole('ADMIN');
    }

    // Define a many-to-many relationship with Team
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id');
    }

    // Define a many-to-many relationship with Team
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    // Check if the user belongs to a specific team
    public function belongsToTeam($teamId)
    {
        return $this->teams->contains('id', $teamId);
    }

    public function scopeDefaultTeam(Builder $query): Team
    {
        return $query->first();
    }

    public function getDefaultTeam(): Team|null
    {
        return $this->teams()->first();
    }


    public function getTeams()
    {
        return $this->teams()->get();
    }

    public function getTeammates()
    {
        return self::whereHas('teams', function ($query) {
            $query->whereIn('teams.id', $this->teams()->pluck('teams.id'));
        })
            ->where('id', '!=', $this->id) // Exclude the current user
            ->distinct()
            ->get();
    }

    public function getSettings()
    {
        // Retrieve the user's default team
        $defaultTeam = $this->defaultTeam;

        // Get default settings
        $defaultSettings = Setting::all();

        // Get default team settings, or an empty collection if no default team exists
        $defaultTeamSettings = $defaultTeam && $defaultTeam->settings->isNotEmpty()
            ? $defaultTeam->settings
            : collect();


        // Merge settings, prioritizing default team settings over global defaults
        $settings = $defaultSettings
            ->keyBy('code') // Key the default settings by 'code'
            ->merge($defaultTeamSettings->keyBy('code')) // Merge team settings, keyed by 'code'
            ->mapWithKeys(function ($setting) {
                return [$setting['code'] => $setting->toArray()]; // Ensure 'code' becomes the array key
            })->toArray();

        return $settings;
    }

    // public function getTenants(Panel $panel): Collection
    // {
    //     return $this->teams;
    // }

    // public function canAccessTenant(Model $tenant): bool
    // {
    //     return $this->teams()->whereKey($tenant)->exists();
    // }
}
