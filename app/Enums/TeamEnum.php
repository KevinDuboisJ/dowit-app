<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TeamEnum: int implements HasLabel
{
    case Bewaking = 4;
    case Revalidatie = 5;
    case SchoonmaakCA = 3;
    case SchoonmaakCD = 9;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Revalidatie => 'Kine/Ergo CA',
        };
    }
}