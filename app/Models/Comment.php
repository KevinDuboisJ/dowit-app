<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\Helper;
use App\Models\TaskStatus;
use App\Traits\HasCreator;
use Illuminate\Support\Facades\Auth;

class Comment extends Model
{
    use HasFactory, HasCreator;

    protected function casts(): array
    {
        return [
            'recipient_users' => 'array',
            'recipient_teams' => 'array',
            'read_by' => 'array',
            'metadata' => 'array',
        ];
    }

    public function scopeByTeamsAndRecipients($query)
    {
        $user = Auth::user();
        $userTeamsIds = $user->teams->pluck('id')->toArray();

        $query->where(function ($query) use ($userTeamsIds, $user) {
            $query->whereHas('task.teams', function ($teamQuery) use ($userTeamsIds) {
                $teamQuery->whereIn('teams.id', $userTeamsIds);
            })
                ->orWhere('created_by', $user->id)
                ->orWhere(function ($query) use ($userTeamsIds, $user) {
                    if (!empty($userTeamsIds)) {
                        $query->whereRaw(
                            implode(' OR ', array_map(function ($teamId) {
                                return "JSON_CONTAINS(recipient_teams, '$teamId')";
                            }, $userTeamsIds))
                        );
                    }

                    $query->orWhereJsonContains('recipient_users', $user->id);
                });
        });
    }

    public function scopeByNotRead($query)
    {
        $query->whereJsonDoesntContain('read_by', Auth::user()->id)
            ->orWhereNull('read_by');
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? Helper::sanitizeHtml($value) : null,
        );
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }
}
