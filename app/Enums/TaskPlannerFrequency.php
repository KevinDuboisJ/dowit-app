<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskPlannerFrequency implements HasLabel
{
    case Daily;
    case Weekly;
    case Monthly;
    case Quarterly;
    case EachXDay;
    case SpecificDays;
    case Weekdays;
    case WeekdayInMonth;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Daily => 'Dagelijks',
            self::Weekly => 'Wekelijks',
            self::Monthly => 'Maandelijks',
            self::Quarterly => 'Per kwartaal',
            self::EachXDay => 'Elke x dag',
            self::SpecificDays => 'Specifieke dagen',
            self::Weekdays => 'Weekdagen',
            self::WeekdayInMonth => 'Weekdag van de maand',
        };
    }
}