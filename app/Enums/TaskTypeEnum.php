<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskTypeEnum: int implements HasLabel
{
    case PatientTransportInBed = 1;
    case Cleaning = 2;
    case PeriodicCheck = 3;
    case SecurityGuardTask = 4;
    case EndOfStayCleaning = 5;
    case PatientTransportInWheelchair = 6;
    case PatientTransportOnFootAssisted = 7;
    case PatientTransportNotify = 8;
    case PatientTransportWithCrutches = 9;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PatientTransportInBed          => 'Patiëntentransport - in bed',
            self::Cleaning                       => 'Poets',
            self::PeriodicCheck                  => 'Periodieke controle',
            self::SecurityGuardTask              => 'Taak bewaking',
            self::EndOfStayCleaning              => 'Eindpoets',
            self::PatientTransportInWheelchair   => 'Patiëntentransport - in rolstoel',
            self::PatientTransportOnFootAssisted => 'Patiëntentransport - te voet begeleid',
            self::PatientTransportNotify         => 'Patiëntentransport - verwittigen',
            self::PatientTransportWithCrutches   => 'Patiëntentransport - met krukken',
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
