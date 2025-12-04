<?php

namespace App\Models;

use App\Contracts\CommentableInterface;
use App\Events\BroadcastEvent;

class Announcement extends Comment implements CommentableInterface
{
    protected $table = 'comments';

    protected function casts(): array
    {
        return [
            'recipient_users' => 'array',
            'recipient_teams' => 'array',
            'read_by' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted()
    {
        static::addGlobalScope('announcements', function ($query) {
            $query->whereNotNull('recipient_users')
                ->orWhereNotNull('recipient_teams');
        });
    }

    public function broadCastMessage(Comment $announcement): void
    {
        broadcast(new BroadcastEvent($announcement, 'announcement_created', 'dashboard-announce'));
    }
}
