<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            'Added',
            'Replaced',
            'Scheduled',
            'InProgress',
            'WaitingForSomeone',
            'Completed',
            'Rejected',
            'FollowUpViaEmail',
            'WaitingForDelivery',
            'Postponed',
            'Paused',
            'Skipped',
        ];

        foreach ($statuses as $status) {
            DB::table('task_statuses')->insert([
                'name' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
