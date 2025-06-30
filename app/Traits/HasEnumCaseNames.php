<?php

namespace App\Traits;

trait HasEnumCaseNames
{
    public static function fromCaseName(string $caseName): self
    {
        return constant("self::$caseName");
    }
}
