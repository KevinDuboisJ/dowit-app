<?php

namespace App\Enums;

enum TeamRole: string
{
    case Execution = 'execution';
    case Visibility = 'visibility';
    case Owner = 'owner';

    public function getLabel(): string
    {
        return match ($this) {
            self::Execution => 'Uitvoering',
            self::Visibility => 'Zichtbaarheid',
            self::Owner => 'Eigenaar',
        };
    }
}