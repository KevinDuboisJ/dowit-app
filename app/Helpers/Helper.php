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
        // 1. Create default config
        $config = HTMLPurifier_Config::createDefault();

        // 2. Allow base64 (data:) images along with the usual schemes
        $config->set('URI.AllowedSchemes', [
            'http'   => true,
            'https'  => true,
            'mailto' => true,
            'ftp'    => true,
            'nntp'   => true,
            'news'   => true,
            'data'   => true,
        ]); // allow data: URIs for base64 images :contentReference[oaicite:0]{index=0}

        $config->set('HTML.TargetBlank',       true); // adds target="_blank" :contentReference[oaicite:1]{index=1}
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        $config->set('HTML.Nofollow',          true); // adds rel="nofollow" :contentReference[oaicite:2]{index=2}
        $config->set('HTML.TargetNoopener',    true); // adds rel="noopener" :contentReference[oaicite:3]{index=3}
        $config->set('HTML.TargetNoreferrer',  true); // adds rel="noreferrer" :contentReference[oaicite:4]{index=4}

        // 5. Purify and return
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
