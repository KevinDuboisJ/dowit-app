<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum TeamEnum: int implements HasLabel
{
    case Revalidatie = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Revalidatie => 'Kine/Ergo CA',
        };
    }
}
