<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum TeamEnum: int implements HasLabel
{
    case KineCA = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::KineCA => 'Kine CA',
        };
    }
}
