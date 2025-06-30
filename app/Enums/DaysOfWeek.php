<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum DaysOfWeek implements HasLabel
{
    use HasEnumCaseNames;

    case Monday;
    case Tuesday;
    case Wednesday;
    case Thursday;
    case Friday;
    case Saturday;
    case Sunday;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Monday => 'Maandag',
            self::Tuesday => 'Dinsdag',
            self::Wednesday => 'Woensdag',
            self::Thursday => 'Donderdag',
            self::Friday => 'Vrijdag',
            self::Saturday => 'Zaterdag',
            self::Sunday => 'Zondag',
        };
    }
}
