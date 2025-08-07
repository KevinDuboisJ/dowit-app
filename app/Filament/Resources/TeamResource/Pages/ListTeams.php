<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false),
        ];
    }

    // public function getTableRecords(): EloquentCollection
    // {
    //     // Fetch and sort the top-level parent teams
    //     $teams = Team::whereNull('parent_team_id')
    //         ->orderBy('name')
    //         ->get();

    //     // Create an EloquentCollection for storing the sorted teams
    //     //$sortedTeams = new EloquentCollection();

    //     // Recursively add teams and their subteams
    //     // foreach ($teams as $team) {
    //     //     self::getSubteams($team, $sortedTeams, 1);
    //     // }

    //     return $teams;
    // }

}
