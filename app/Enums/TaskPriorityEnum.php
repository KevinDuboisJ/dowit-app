<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskPriorityEnum: string implements HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Low => 'Laag',
            self::Medium => 'Gemiddeld',
            self::High => 'Hoog',
        };
    }
}