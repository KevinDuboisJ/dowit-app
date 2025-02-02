<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Logger;

class AdminStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Define the current month
        $currentMonth = Carbon::now()->month;

        // Count the records for the current month
        $currentMonthCount = Logger::whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        return [
            Stat::make('Gebruikers', User::count())
                ->description('Alle gebruikers uit de database.')
                ->descriptionIcon('heroicon-o-users'),
            Stat::make('Logs', Logger::count())
                ->description("Deze maand zijn er $currentMonthCount logs toegevoegd.")
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
        ];
    }
}
