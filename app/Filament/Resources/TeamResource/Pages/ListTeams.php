<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Team;
use App\Traits\HasTeams;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;

class ListTeams extends ListRecords
{
    use HasTeams;
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data) {

                    $data['user_id'] = Auth::id();
                    return $data;
                }),
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
