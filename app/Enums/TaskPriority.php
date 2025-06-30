<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum TaskPriority implements HasLabel
{
    use HasEnumCaseNames;

    case Low;
    case Medium;
    case High;


    public function getLabel(): ?string
    {
        return match ($this) {
            self::Low => 'Laag',
            self::Medium => 'Gemiddeld',
            self::High => 'Hoog',
        };
    }
}