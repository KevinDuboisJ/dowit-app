<?php

namespace App\Enums;

enum EventEnum: string
{
    case TaskCreated = 'task_created';
    case TaskStarted = 'task_started';
    case TaskUpdated = 'task_updated';
    case TaskRejected = 'task_rejected';
    case TaskCompleted = 'task_completed';
    case TaskHelpRequested = 'task_help_requested';
    case TaskHelpGiven = 'task_help_given';
    case Announcement = 'announcement';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TaskCreated => 'Taak aangemaakt',
            self::TaskStarted => 'Taak gestart',
            self::TaskUpdated => 'Taak geüpdatet',
            self::TaskRejected => 'Taak afgewezen',
            self::TaskCompleted => 'Taak afgehandeld',
            self::TaskHelpRequested => 'Hulp gevraagd',
            self::TaskHelpGiven => 'Hulp gegeven',
            self::Announcement => 'Aankondiging',
        };
    }
}
