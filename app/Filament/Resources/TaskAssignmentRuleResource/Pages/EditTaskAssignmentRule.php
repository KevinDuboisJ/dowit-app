<?php

namespace App\Filament\Resources\TaskAssignmentRuleResource\Pages;

use App\Filament\Resources\TaskAssignmentRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskAssignmentRule extends EditRecord
{
    protected static string $resource = TaskAssignmentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
