<?php

namespace App\Filament\Resources\TaskPlannerResource\RelationManagers;

use App\Models\Task;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        // we’re not using this form for creating, only in the edit action
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title'),
                Tables\Columns\TextColumn::make('due_at')->dateTime('Y-m-d H:i'),
                // …your other columns…
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    // use our shared schema:
                    ->form(static::$resource::getTaskFormSchema())
                    ->modalHeading('Edit Task')
                    ->modalButton('Save')
                    ->action(function (Task $record, array $data) {
                        $record->update($data);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // …if you have bulk deletes, etc.
                ]),
            ]);
    }
}
