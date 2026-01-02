<?php

namespace App\Models;

use App\Enums\TaskStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\Helper;
use App\Models\TaskStatus;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Comment extends Model
{
    use SoftDeletes, HasFactory, HasCreator;

    protected $appends = [
        'is_completed',
    ];

    protected $fillable = [
        'content',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recipient_users' => 'array',
            'recipient_teams' => 'array',
            'read_by' => 'array',
            'metadata' => 'array',
        ];
    }

    public function scopeByTeams(Builder $query): Builder
    {
        $teamIds = Auth::user()->getTeamIds();

        return $query->where(function (Builder $q) use ($teamIds) {
            // 1) User is in one of the task's teams
            $q->where(function (Builder $inner) use ($teamIds) {
                $inner->byTaskTeams($teamIds);
            })
                ->orWhere(function (Builder $inner) use ($teamIds) {
                    $inner->byRecipientTeams($teamIds);
                });
        });
    }

    // User teams is in one of the task's teams
    public function scopeByTaskTeams(Builder $query, array $teamIds): Builder
    {
        if (empty($teamIds)) {
            return $query;
        }

        return $query->whereHas('task.teams', function (Builder $teamQuery) use ($teamIds) {
            $teamQuery->whereIn('teams.id', $teamIds);
        });
    }

    // User teams is in one of the recipient's team
    public function scopeByRecipientTeams(Builder $query, array $teamIds): Builder
    {
        if (empty($teamIds)) {
            return $query;
        }

        // (recipient_teams is a JSON array of team IDs)
        return $query->where(function (Builder $q) use ($teamIds) {
            foreach ($teamIds as $teamId) {
                $q->orWhereJsonContains('recipient_teams', $teamId);
            }
        });
    }

    public function scopeByNotRead($query)
    {
        $query->whereJsonDoesntContain('read_by', Auth::user()->id)
            ->orWhereNull('read_by');
    }

    protected function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => !is_null($this->status_id) && !TaskStatusEnum::isActiveStatus($this->status_id),
        );
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
