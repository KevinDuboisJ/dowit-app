<?php

namespace App\Filament\Resources\TaskChainResource\Pages;

use App\Filament\Resources\TaskChainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaskChains extends ListRecords
{
    protected static string $resource = TaskChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
