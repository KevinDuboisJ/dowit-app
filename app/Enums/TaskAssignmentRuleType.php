<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskAssignmentRuleType: string implements HasLabel
{
    case Execution = 'execution';
    case Visibility = 'visibility';

    public function getLabel(): string
    {
        return match ($this) {
            self::Execution => 'Uitvoerend team',
            self::Visibility => 'Zichtbaarheid',
        };
    }
}
