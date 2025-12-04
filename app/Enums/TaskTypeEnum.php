<?php

namespace App\Enums;

use App\Traits\HasEnumCaseNames;
use Filament\Support\Contracts\HasLabel;

enum TaskTypeEnum: int implements HasLabel
{
    use HasEnumCaseNames;

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

    public static function fromCollection($collection): array
    {
        return $collection
            ->mapWithKeys(function ($item) {
                $enum = self::from($item->id);
                return [$enum->name => $enum];
            })
            ->all();
    }

    public function isPatientTransport(): bool
    {
        return in_array($this, [
            self::PatientTransportInBed,
            self::PatientTransportInWheelchair,
            self::PatientTransportOnFootAssisted,
            self::PatientTransportNotify,
            self::PatientTransportWithCrutches,
        ], true);
    }
}
