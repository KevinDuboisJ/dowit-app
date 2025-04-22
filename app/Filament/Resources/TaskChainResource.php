<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskChainResource\Pages;
use App\Models\TaskChain;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use App\Enums\TaskPlannerFrequency;
use App\Enums\ApplyOnHoliday;
use App\Enums\TaskPlannerAction;
use App\Enums\DaysOfWeek;
use App\Enums\TaskStatus;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Support\Carbon;
use App\Services\TaskPlannerService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Set;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Services\TaskAssignmentService;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\TaskChainResource\Pages\CreateTaskChainWizard;

class TaskChainResource extends Resource
{
    protected static ?string $model = TaskChain::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Taakconfigurator';

    protected static ?string $modelLabel = 'Keten';

    protected static ?string $pluralModelLabel = 'ketens';

    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Ketting naam'),
            TextColumn::make('trigger_event')->label('Event'),
            TextColumn::make('trigger_source')->label('Source'),
            IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTaskChains::route('/'),
            'create' => CreateTaskChainWizard::route('/create'),
            'edit'   => Pages\EditTaskChain::route('/{record}/edit'),
        ];
    }
}
