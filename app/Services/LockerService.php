<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Enums\TaskTypeEnum;
use App\Enums\TeamEnum;
use App\Events\BroadcastEvent;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Support\Facades\DB;

class LockerService
{
    public function getLockersWithInactiveContracts(): array
    {
        $formbuilderDB = DB::connection('formbuilder')->getDatabaseName();
        $edbDB = DB::connection('edb')->getDatabaseName();

        $query = "
            SELECT *
            FROM {$formbuilderDB}.locker
            LEFT JOIN {$formbuilderDB}.campus ON lo_ca_id = ca_id
            LEFT JOIN {$formbuilderDB}.form000086 ON NUM02 = lo_id AND deleted_by IS NULL
            LEFT JOIN {$edbDB}.person ON pe_id = NUM03
            LEFT JOIN {$edbDB}.contract c ON c.cn_pe_id = pe_id
                AND c.cn_id = (
                    SELECT MAX(c2.cn_id)
                    FROM {$edbDB}.contract c2
                    WHERE c2.cn_pe_id = pe_id
                    AND c2.cn_inactive = '1'
                )
            LEFT JOIN {$edbDB}.department ON de_id = cn_de_id
            WHERE pe_id IS NOT NULL
            AND c.cn_id IS NOT NULL
            AND NOT EXISTS (
                    SELECT 1
                    FROM {$edbDB}.contract c3
                    WHERE c3.cn_pe_id = pe_id
                    AND c3.cn_inactive = '0'
            )
            LIMIT 1
        ";

        return DB::connection('formbuilder')->select($query);
    }

    public function handleUnlockTasks(TaskService $taskService): void
    {
        $results = $this->getLockersWithInactiveContracts();
        $config = self::config();

        foreach ($results as $row) {
            $lockerId = $row->lo_id;
            $lockerNumber = $row->lo_number;
            $campusId = $row->lo_ca_id;
            $gender = $config['genders'][$row->lo_gender] ?? null;
            $location = $config['locations'][$row->lo_location] ?? null;
            $lockerType = $config['lockerTypes'][$row->lo_type] ?? null;

            $activeTaskAlreadyExists = Task::query()
                ->where('locker_id', $lockerId)
                ->whereIn('status_id', TaskStatusEnum::activeStatuses())
                ->exists();

            if ($activeTaskAlreadyExists) {
                logger("Skipping locker $lockerNumber: active task already exists.");
                continue;
            }

            $task = $taskService->create([
                'task' => [
                    'start_date_time' => now(),
                    'name' => "Locker ontgrendelen",
                    'description' => "$lockerNumber, $location($gender), $lockerType",
                    'locker_id' => $lockerId,
                    'status_id' => TaskStatusEnum::Added->value,
                    'campus_id' => $campusId,
                    'task_type_id' => TaskTypeEnum::UnlockLocker->value,
                ],
                'teamsMatchingAssignment' => [TeamEnum::Bewaking->value],
            ]);

            broadcast(new BroadcastEvent($task, 'task_created', 'handle-lockers'));

            logger("Task created for locker {$lockerId}.");
        }
    }


    public static function config(): array
    {
        return [
            'genders' => [
                1 => "M",
                2 => "V",
            ],

            'locations' => [
                1 => "CA Kleedkamer A (OK)",
                2 => "CA Kleedkamer B (OK)",
                3 => "CA Kleedkamer C",
                4 => "CA Kleedkamer D",
                5 => "CA Kleedkamer F",
                6 => "CA Kleedkamer G",
                7 => "CD Kleedkamer D-1M",
                8 => "CD Kleedkamer D-1V",
            ],

            'lockerTypes' => [
                1 => "kast met eigen hangslot",
                2 => "Kast met hangslot",
                3 => "Met code",
                4 => "Kast met sleutel",
            ],

            'audience' => [
                1 => "MEDEWERKER",
                2 => "STAGIARE",
                3 => "MEDEWERKER CA",
                4 => "REVA STAGIAIRE",
                5 => "STAGEBEGELEIDER",
                6 => "OOGKLINIEK",
                7 => "MEDEWERKER RX CA",
                8 => "VRIJWILLIGERS",
                9 => "EXTERNE MATERNITEIT",
                10 => "LABO 2 CAMPUSSEN",
                11 => "INVALLER INZO",
                12 => "STUDENT KINE",
                13 => "CA-CD INZO",
                14 => "OOGARTSEN LASERKLINIEK",
                15 => "APOTHEEK"
            ],

            'usageTypes' => [
                1 => "Individueel",
                2 => "Gedeeld",
            ],

            'admins' => [
                1 => "Cecile/Greetje",
                2 => "Elke Snoeks",
                3 => "Ellen Krekels",
                4 => "Jasper",
                5 => "Karen de Vocht",
                6 => "Labo",
                7 => "Natascha",
                8 => "Personeelsdienst",
                9 => "Sleutel zit op deur"
            ]
        ];
    }

    public function executeChainAction($context)
    {
        $connection = DB::connection('formbuilder');

        $connection->transaction(function () use ($connection, $context) {
            // Disable strict mode for this session
            $connection->statement("SET SESSION sql_mode=''");

            // Update existing rows
            $connection->table('form000086')
                ->where('NUM02', $context->locker_id)
                ->whereNull('deleted_by')
                ->update([
                    'deleted' => now(),
                    'deleted_by' => 14,
                ]);

            // Insert new row
            $connection->table('form000086')
                ->insert([
                    'created' => now(),
                    'created_by' => 14,
                    'NUM01' => 14,
                    'NUM02' => $context->locker_id,
                    'NUM03' => 0,
                    'NUM04' => null,
                    'NUM05' => 0,
                ]);
        });
    }
}
