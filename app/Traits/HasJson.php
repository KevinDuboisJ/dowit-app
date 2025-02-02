<?php

namespace App\Traits;

trait HasJson
{

    public function getDataAttribute($data): array //getAttributeNameAttribute takes precedence over casts.
    {
        $array = json_decode($data, true) ?? [];
        $columns = $array['columns'] ?? [];

        foreach ($columns as $key => $item) {

            $array['columns'][$key] = $item;
        }

        return $array;
    }

    public static function createJsonHistory(array $diffKeys, array $previousRow, array $currentRow): array
    {
        $data = [];

        foreach ($diffKeys as $key) {
            $data['columns'][$key] = ['previousState' => ['value' => $previousRow[$key]], 'currentState' => ['value' => $currentRow[$key]]];
        }

        return $data;
    }

    public function getCurrentState()
    {
        $currentState = [];
        $data = $this->data['columns'];

        foreach ($data as $key => $state) {
            $currentState[$key] = $state['currentState']['value'];
        }

        return $currentState;
    }

    public function getPreviousState()
    {
        $previousState = [];
        $data = $this->data['columns'];

        foreach ($data as $key => $state) {
            $previousState[$key] = $state['previousState']['value'];
        }

        return $previousState;
    }
}
