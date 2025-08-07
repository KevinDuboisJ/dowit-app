<?php

namespace App\Traits;

trait HasEnumCaseNames
{
    // Pure enums don’t support from or tryFrom as those methods gets the enum case by the backed value. (This doesn't work: DaysOfWeek::tryFrom('monday') because DaysOfWeek has no backed values)
    // there’s no built-in PHP function to get enum case by its name dynamically
    // This trait adds support for resolving cases by name, working for both pure and backed enums. fromCaseName returns a enum case object
    public static function fromCaseName(string $caseName): ?self
    {
        $cases = array_column(self::cases(), 'name');

        if (!in_array($caseName, $cases, true)) {
            return null;
        }

        return constant(self::class . "::$caseName");
    }
}
