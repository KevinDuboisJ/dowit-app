<?php

namespace App\Contracts;

use App\Models\Comment;

interface CommentableInterface
{
    public function broadcastMessage(Comment $comment): void;
}
