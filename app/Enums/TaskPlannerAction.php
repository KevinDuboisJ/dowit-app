<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskPlannerAction implements HasLabel
{
    case Add;
    case Replace;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Add => 'Toevoegen',
            self::Replace => 'Vervangen',
        };
    }
}
