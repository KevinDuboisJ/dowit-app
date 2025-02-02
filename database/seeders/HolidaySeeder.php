<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Spatie\Holidays\Holidays;
use Spatie\Holidays\Countries\Belgium;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Fetch public holidays for Belgium and the current year
            $holidays = Holidays::for(Belgium::make(), Carbon::now()->year)->get();

            // Seed or update public holidays
            foreach ($holidays as $holiday) {
                Holiday::updateOrCreate(
                    ['name' => $holiday['name']],
                    ['date' => $holiday['date'], 'public' => true, 'created_by' => config('app.system_user_id'), 'deleted_at' => null]
                );
            }

            // Extract dates of fetched holidays as strings (Y-m-d)
            $holidayDates = collect($holidays)
                ->pluck('date')
                ->map(fn($date) => $date->toDateString()) // Convert Carbon objects to strings
                ->toArray();

            // Soft delete holidays that are no longer in the fetched list
            Holiday::where('public', true)
                ->whereNotIn('date', $holidayDates) // Compare dates in the correct format
                ->update(['deleted_at' => now()]); // Mark as soft deleted

            Log::info('Public holidays updated successfully for the year ' . now()->year);
        } catch (\Exception $e) {
            Log::error('Failed to update public holidays: ' . $e->getMessage());
        }
    }
}
