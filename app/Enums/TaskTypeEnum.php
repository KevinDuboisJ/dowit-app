<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum TaskTypeEnum: int implements HasLabel
{
    case PatientTransportInBed = 1;
    case PatientTransportInWheelchair = 4;
    case PatientTransportOnFootAssisted = 5;
    case PatientTransportNotify = 6;
    case PatientTransportWithCrutches = 7;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PatientTransportInBed => 'Patiëntentransport - in bed',
        };
    }

    public static function getPatientTransportIds(): array
    {
        return [
            self::PatientTransportInBed->value,
            self::PatientTransportInWheelchair->value,
            self::PatientTransportOnFootAssisted->value,
            self::PatientTransportNotify->value,
            self::PatientTransportWithCrutches->value,
        ];
    }
}
