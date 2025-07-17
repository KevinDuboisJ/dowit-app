<?php

namespace App\Filament\Components;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;

class PatientAutocomplete extends Field
{
  protected string $view = 'filament.components.patient-autocomplete';
}
