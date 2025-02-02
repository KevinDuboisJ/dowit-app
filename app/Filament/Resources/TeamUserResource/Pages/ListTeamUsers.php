<?php

namespace App\Filament\Resources\TeamUserResource\Pages;

use App\Filament\Resources\TeamUserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use App\Traits\HasParentResource;
use Filament\Forms\Components\Select;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ListTeamUsers extends ListRecords
{
    use HasParentResource;

    protected static string $resource = TeamUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignTeamUser')

                ->label('Gebruiker toewijzen')
                ->visible(fn() => $this->parent->autoassign_rules === null)
                ->form([
                    Select::make('user_id')
                        ->label('Selecteer gebruiker')
                        ->options(User::excludeSystemUser()->pluck('username', 'id')) // Fetch all users
                        ->searchable() // Allow searching for users
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Assign the selected user to the parent team
                    $this->parent->users()->attach($data['user_id']);
                })
                ->modalHeading('Gebruiker toewijzen aan team')
                ->ModalSubmitActionLabel('Toewijzen')
            // ->successNotificationTitle('Gebruiker succesvol toegewezen!')
        ];
    }
}
