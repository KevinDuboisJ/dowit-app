<?php

namespace App\Filament\Resources\TaskAssignmentRuleResource\Pages;

use App\Filament\Resources\TaskAssignmentRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaskAssignmentRules extends ListRecords
{
    protected static string $resource = TaskAssignmentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->createAnother(false),
        ];
    }
}
