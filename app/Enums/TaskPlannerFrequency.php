<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskPlannerFrequency implements HasLabel
{
    case Daily;
    case Weekly;
    //case Monthly;
    case EachXDay;
    case SpecificDays;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Daily => 'Dagelijks',
            self::Weekly => 'Wekelijks',
            //self::Monthly => 'Maandelijks',
            self::EachXDay => 'Elke x dag',
            self::SpecificDays => 'Specifieke dagen',
        };
    }
}