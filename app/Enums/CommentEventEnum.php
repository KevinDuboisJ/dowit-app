<?php

namespace App\Enums;

enum CommentEventEnum: string
{
    case TaskCreated = 'task_created';
    case TaskStarted = 'task_started';
    case TaskUpdated = 'task_updated';
    case TaskRejected = 'task_rejected';
    case TaskCompleted = 'task_completed';
    case TaskHelpRequested = 'task_help_requested';
    case TaskHelpGiven = 'task_help_given';
    case Announcement = 'announcement';
}