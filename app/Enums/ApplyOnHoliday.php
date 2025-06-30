<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ApplyOnHoliday implements HasLabel
{
    case Yes;
    case No;
    case OnlyOnHolidays;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Yes => 'Ja',
            self::No => 'Nee',
            self::OnlyOnHolidays => 'Alleen op feestdagen',
        };
    }
}
