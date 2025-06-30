<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: int implements HasLabel
{
    use HasEnumCaseNames;

    case Added = 1;
    case Replaced = 2;
    case InProgress = 4;
    case WaitingForSomeone = 5;
    case Completed = 6;
    case Skipped = 12;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Added => 'Toegevoegd',
            self::Replaced => 'Vervangen',
            self::InProgress => 'In verwerking',
            self::WaitingForSomeone => 'Wacht op iemand',
            self::Completed => 'Afgehandeld',
            self::Skipped => 'Overgeslagen',
        };
    }
}
