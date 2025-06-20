<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DaysOfWeek implements HasLabel
{
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

    public static function fromName(string $name)
    {
        return constant("self::$name");
    }
}
