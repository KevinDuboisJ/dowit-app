<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum ChainActionType implements HasLabel
{
    use HasEnumCaseNames;
    
    case CreateTask;
    case CustomCode;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CreateTask => 'Taak aanmaken',
            self::CustomCode => 'Aangepast script',
        };
    }
}