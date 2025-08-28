<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasPatientListTable
{
    /**
     * Note:
     * 
     * protected $connection = 'patientlist' only affects which PDO connection
     * Eloquent uses when running standalone queries on the Visit model.
     *
     * In a whereHas() for example, Laravel generates one single SQL statement (Example in TaskPlannerResource, TextColumn::make('task_type_label'))
     * on the parent model’s connection (e.g. dowittest) and inlines the subquery.
     * Because the subquery’s table name is unqualified (just "visits"),
     * MySQL tries to resolve it as dowittest.visits, which doesn’t exist, resulting in the error.
     */

    /**
     * Automatically configure the model to point to the patientlist schema.
     * - Uses the app's default DB connection (same server).
     * - Table name is derived from the model's class (snake, plural).
     * - Schema is `patientlist` or `patientlisttest` when APP_ENV=local.
     */

    
    protected function initializeHasPatientListTable(): void
    {
        // Use the default connection (same PDO; allows cross-schema queries)
        $this->setConnection('patientlist');

        // Derive table from model class: Visit -> visits, PatientVisit -> patient_visits
        $baseTable = Str::snake(Str::pluralStudly(class_basename(static::class)));

        // Choose schema based on environment
        $schema = 'patientlist' . (app()->environment('local') ? 'test' : '');

        // Set fully-qualified table: schema.table
        $this->setTable($schema . '.' . $baseTable);
    }
}
