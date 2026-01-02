<?php

namespace App\Console\Commands;

use Database\Seeders\HolidaySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class FailoverHolidaySeed extends Command
{
    protected $signature = 'failover:holidays-seed';
    protected $description = 'Checks if all holidays are updated for the current year and retries if necessary';

    public function handle()
    {
        $currentYear = now()->year;

        // Check if any holiday's `updated_at` does not contain the current year
        $outdatedHolidays = DB::table('holidays')
            ->where('public', true) // Only check for public holidays
            ->whereNull('deleted_at') // Only check for public holidays
            ->where(function ($query) use ($currentYear) {
                $query->whereYear('date', '<', $currentYear)
                    ->orWhereNull('date');
            })
            ->exists();

        if ($outdatedHolidays) {
            // Retry the holiday seed command
            $this->warn("Not all holidays are updated for the current year. Retrying...");
            $this->sendFailureNotification();
            $this->call(HolidaySeeder::class);

            // Recheck after retrying
            $outdatedHolidays = DB::table('holidays')
                ->where('public', 1) // Only check for public holidays
                ->where(function ($query) use ($currentYear) {
                    $query->whereYear('updated_at', '<', $currentYear)
                        ->orWhereNull('updated_at');
                })
                ->exists();

            if (!$outdatedHolidays) {
                $this->info("Holidays table successfully updated after retry.");
            }
        }
    }

    private function sendFailureNotification()
    {
        Mail::raw(
            "De cronjob voor het bijwerken van feestdagen is mislukt. Er is inmiddels een nieuwe poging gedaan, dus het probleem is mogelijk al opgelost, maar controleer dit voor de zekerheid.",
            function ($message) {
                $to = 'kevin.dubois@azmonica.be';
                $server = gethostname();
                $subject = "Dowit - Fout bij jaarlijkse Bijwerken Feestdagen [{$server}]";
                $message->to($to)->subject($subject);
            }
        );
    }
}
