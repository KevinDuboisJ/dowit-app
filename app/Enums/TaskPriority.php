<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskPriority implements HasLabel
{
    case Low;
    case Medium;
    case High;


    public function getLabel(): ?string
    {
        return match ($this) {
            self::Low => 'Laag',
            self::Medium => 'Gemiddeld',
            self::High => 'Hoog',
        };
    }

    public static function fromCaseName(string $caseName): self
    {
        return constant("self::$caseName");
    }
}