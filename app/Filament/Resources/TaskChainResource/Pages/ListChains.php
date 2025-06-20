<?php

namespace App\Filament\Resources\ChainResource\Pages;

use App\Filament\Resources\ChainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

class ListChains extends ListRecords
{
    protected static string $resource = ChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableRecords(): Collection | Paginator | CursorPaginator
    {
        $records = parent::getTableRecords();

        // Collect all IDs used in the current page
        $spaceIds = [];
        $campusIds = [];
        $taskTypeIds = [];

        foreach ($records as $record) {
            $task = $record->actions['createTask'] ?? null;
            if (!is_array($task)) continue;

            if (isset($task['space_id'])) {
                $spaceIds[] = $task['space_id'];
            }
            if (isset($task['campus_id'])) {
                $campusIds[] = $task['campus_id'];
            }
            if (isset($task['task_type_id'])) {
                $taskTypeIds[] = $task['task_type_id'];
            }
        }

        // Cache only relevant model data for this page
        \App\Filament\Resources\ChainResource::cacheLookupsForPage(
            $spaceIds,
            $campusIds,
            $taskTypeIds
        );

        return $records;
    }
}
