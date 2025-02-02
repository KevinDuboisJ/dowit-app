<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use HTMLPurifier;
use HTMLPurifier_Config;

class Helper
{
    // Array structure has to be the same for both arrays when comparing the hashes.
    public static function toHash(array $data)
    {
        $values = [];
        foreach ($data as $row) {
            if (is_array($row)) {
                $values[] = self::toHash($row);
            } else
                $values[] = implode(',', (array) $row);
        }

        return hash('sha256', implode('', $values));
    }

    public static function sortArrayByKey(&$array): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::sortArrayByKey($value);
            }
        }
        ksort($array);
    }

    // Return the keys where the key or value is diferent. The data type is not taken into account.
    public static function diffKeysByValAndKey(array $record_1, array $record_2): array
    {
        return array_keys(array_diff_assoc((array) $record_1, (array) $record_2));
    }

    public static function castAttributesToString($item)
    {
        $attributes = [];

        switch ($item) {

            case $item instanceof Model:

                foreach ($item->getRawOriginal() as $key => $value) {
                    $attributes[$key] = self::valueToString($value);
                };
                break;

            case is_object($item):
                foreach ($item as $key => $value) {
                    $attributes[$key] = self::valueToString($value);
                };
                break;

            case is_array($item):
                foreach ($item as $key => $value) {
                    $attributes[$key] = self::valueToString($value);
                };
                break;
        }

        return $attributes;
    }

    public static function castDataToString($data)
    {
        if ($data instanceof Collection || $data instanceof EloquentCollection) {
            $data = clone $data;
            return $data->map(function ($el) {
                return self::castAttributesToString($el);
            });
            return $data;
        }

        if ($data instanceof Model) {
            $data = clone $data;
            return self::castAttributesToString($data);
        }

        if (is_array($data)) {
            return self::castAttributesToString($data);
        }

        Log::warning("In castDataToString, data is not identified with any recognized type");
    }

    public static function valueToString($value)
    {
        return !is_null($value) ? (string) $value : null;
    }

    public static function containsFilter(array $filters, string $value): array
    {
        return array_filter($filters, fn($filter) => isset($filter['field']) && $filter['field'] === $value);
    }

    public static function sanitizeHtml($html)
    {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
