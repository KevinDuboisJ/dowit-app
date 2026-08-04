<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public $preserveKeys = true;
    private array $appends = [];

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'edb_id' => $this->edb_id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'username' => $this->username,
            'department_id' => $this->department_id,
            'profession_id' => $this->profession_id,

            'last_login_at' => $this->last_login_at,
            'last_seen_at' => $this->last_seen_at,
            'last_logout_at' => $this->last_logout_at,

            'object_sid' => $this->object_sid,
            'image_path' => $this->image_path,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'roles' => $this->roles,

            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
            ])->values()),
            
            ...$this->appends
        ];
    }

    public function append(array $data)
    {
        $this->appends = [...$this->appends, ...$data];
    }
}