<?php

namespace App\Filament\Resources\TaskChainResource\Pages;

use App\Filament\Resources\TaskChainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskChain extends EditRecord
{
    protected static string $resource = TaskChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
