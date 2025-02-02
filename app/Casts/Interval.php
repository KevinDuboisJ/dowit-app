<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Enums\DaysOfWeek;

class Interval implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // Decode the JSON
        $data = json_decode($value, true);

        // Return array if it is an array, otherwise return as string
        return is_array($data) ? $data : (string) $data;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (!is_array($value) && !is_object($value)) {
            // Decode the JSON value to convert it to an array
            return json_encode($value);
        }

        return json_encode($value); // Store the array as a JSON string
    }
}
