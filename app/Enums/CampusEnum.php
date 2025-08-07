<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum CampusEnum: int implements HasLabel
{
    case CA = 1;
    case CD = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CA => 'Campus Antwerpen',
            self::CD => 'Campus Deurne',
        };
    }
}
