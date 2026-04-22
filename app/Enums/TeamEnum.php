<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TeamEnum: int implements HasLabel
{
    case Bewaking = 4;
    case Revalidatie = 5;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Revalidatie => 'Kine/Ergo CA',
        };
    }
}
