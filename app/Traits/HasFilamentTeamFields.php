<?php

namespace App\Traits;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;


trait HasFilamentTeamFields
{
    public static function belongsToTeamsField(string $label = 'Teams', string $tooltip='Teams met toegang tot dit item'): Select
    {
        return Select::make('teams')
            ->label($label)
            ->multiple()
            ->relationship('teams', 'name')
            ->options(fn() => Auth::user()->teams()->pluck('name', 'teams.id')->toArray())
            ->default(fn() => Auth::user()->teams()->pluck('teams.id')->toArray())
            ->hint(
                new HtmlString(view('filament.components.hint-icon', [
                    'tooltip' => $tooltip,
                ])->render())
            )
            ->required();
    }

    public static function creatorField(): ?Placeholder
    {
        return Placeholder::make('')
            ->visible(fn($record) => $record?->creator !== null)
            ->content(fn($record): HtmlString => new HtmlString(
                '<span class="text-sm font-extralight text-gray-500">Aangemaakt door: ' .
                    e($record->creator->firstname . ' ' . $record->creator->lastname) .
                    '</span>'
            ))
            ->columnSpan('full')
            ->extraAttributes([
                'class' => 'az-creator-field',
            ]);
    }

    public static function belongsToTeamsSection(string $sectionTitle = 'Behering'): Section
    {
        return Section::make($sectionTitle)
            ->schema([
                static::belongsToTeamsField(),
            ]);
    }
}
