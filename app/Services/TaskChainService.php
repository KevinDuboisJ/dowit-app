<?php

namespace App\Services;

use App\Models\TaskChain;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TaskChainService
{
    public function handleEvent(string $event, array $contextData = [])
    {
        // Retrieve active chains matching the trigger event and source (if provided)
        $chains = TaskChain::where('trigger_event', $event)
            ->where('is_active', true)
            // Optionally filter by trigger_source:
            // ->where('trigger_source', $contextData['source'] ?? 'internal')
            ->get();

        foreach ($chains as $chain) {
            // Optionally, check chain's conditions against $contextData
            if ($this->conditionsMatch($chain->conditions, $contextData)) {
                // Create the task based on defined task_type
                // You could reuse your TaskPlannerService here:
                // TaskPlannerService::createTask(...);
                Log::info("TaskChain triggered: " . $chain->name);
                // If using multi-step chains, schedule the steps accordingly.
            }
        }
    }

    protected function conditionsMatch($chainConditions, array $contextData): bool
    {
        // Implement your own condition matching logic.
        // For example, if chainConditions is stored as JSON:
        // [
        //     "campus_id" => 3,
        //     "space_id" => 5
        // ]
        // Compare these values with $contextData.
        if (!$chainConditions) {
            return true;
        }
        $conditions = json_decode($chainConditions, true);
        foreach ($conditions as $key => $value) {
            if (!isset($contextData[$key]) || $contextData[$key] != $value) {
                return false;
            }
        }
        return true;
    }
}