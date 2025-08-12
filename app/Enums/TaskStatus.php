<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum TaskStatus: int implements HasLabel
{
    use HasEnumCaseNames;

    case Added = 1;
    case Replaced = 2;
    case Scheduled = 3;
    case InProgress = 4;
    case WaitingForSomeone = 5;
    case Completed = 6;
    case Rejected = 7;
    case FollowUpViaEmail = 8;
    case WaitingForDelivery = 9;
    case Postponed = 10;
    case Paused = 11;
    case Skipped = 12;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Added => 'Toegevoegd',
            self::Replaced => 'Vervangen',
            self::Scheduled => 'Gepland',
            self::InProgress => 'In verwerking',
            self::WaitingForSomeone => 'Wacht op iemand',
            self::Completed => 'Afgehandeld',
            self::Skipped => 'Overgeslagen',
        };
    }

    public static function fromStartDateTime(Carbon $startDateTime): self
    {
        return $startDateTime->isFuture()
            ? self::Scheduled
            : self::Added;
    }
}
