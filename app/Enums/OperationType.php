<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OperationType implements HasLabel
{
    case CreateTask;
    case Custom;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CreateTask => 'Taak aanmaken',
            self::Custom => 'Aangepast script',
        };
    }
}