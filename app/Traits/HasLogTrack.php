<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Logger;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use App\Helpers\Helper;

trait HasLogTrack
{
    protected function track(Model $model, $event, $comment, callable $func = null, $class = null, $id = null): bool
    {
        // Allow for overriding of table if it's not the model table
        $class = $class ?: $model->getMorphClass();

        // Allow for overriding of id if it's not the model id
        $id = $id ?: $model->id;

        // Allow for customization of the history record if needed
        $func = $func ?: [$this, 'getHistoryBody'];

        // Original values before model was manipulated.
        $originalValues = $model->getRawOriginal();

        // Dirty attributes are those that have been modified.
        $dirtyAttributes = collect(Arr::except($model->getDirty(), ['created_at', 'updated_at']));
       
        // Iterate dirty attributes and process them.
        $columns = $dirtyAttributes
            ->map(function ($currentDisplayValue, $field) use ($func, $originalValues, $model) {

                $originalValue = $originalValues[$field] ?? null;

                //Add relationship value(name)
                if (substr($field, -3) == '_id' && method_exists($model, $relationName = Str::camel(substr($field, 0, -3)))) {
                    
                    $name = ($relationName == 'company' ? 'cy_name':'name');
                    
                    // When you access a relationship in Laravel using the property syntax (e.g., $model->$relationName->name), you're accessing the "loaded" relation.
                    // This means that the related model or collection of models was loaded previously (perhaps implicitly when you first accessed the relation),
                    // and that data is cached with the parent model. If you then update the related model directly, the parent model's cached relation data isn't automatically updated.
                    $previousDisplayValue = $originalValue ? $model->$relationName->$name : null;

                    // I have to use $relationName() to query the relationship again based on the updating value. I can't use fresh() cause on updating it isn't stored yet in the database.
                    // Even if i was doing this on a update instead of updating i would have to use fresh() cause this way i would get the new data instead of the models cached relation data(The relationship with the old id)
                    $currentDisplayValue = $model->$relationName()->value($name);

                    $previousRefValue = $originalValue;
                    $currentRefValue = $model->$field;

                    return call_user_func_array($func, [$previousDisplayValue, $currentDisplayValue, $previousRefValue, $currentRefValue]);
                }
                // $originalValues[$field] ?? null : Use null in case it is a creation, $originalValues would be empty.
                return call_user_func_array($func, [$originalValue, $currentDisplayValue]);
            });
      
        Logger::create([
            'traceable_id'    => $id,
            'traceable_type'  => $class,
            'user_id'         => Auth::user()->id ?? config('app.system_user_id'),
            'source'          => pathinfo(__FILE__, PATHINFO_FILENAME),
            'event'           => $event,
            'level'           => 'INFO',
            'data'            => ['columns' => $columns],
            'comment'         => $comment,
        ]);
        return true;
    }

    // $previousRefValue && $currentRefValue is the id in case it is a column with a foreign key.
    protected function getHistoryBody($previousDisplayValue, $currentDisplayValue, $previousRefValue = null, $currentRefValue = null)
    {
        $columns = [
            'previousState' => ['value' => $previousDisplayValue],
            'currentState' => ['value' => $currentDisplayValue],
        ];

        if ($previousRefValue !== null || $currentRefValue !== null) {
            $columns['previousState'] = ['id' => $previousRefValue] + $columns['previousState'];
            $columns['currentState'] = ['id' => $currentRefValue] + $columns['currentState'];
        }

        return $columns;
    }
}
