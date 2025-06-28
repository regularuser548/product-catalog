<?php

namespace App\Traits;

trait FilterUtils
{
    private function sortFilters(array &$filters): void
    {
        foreach ($filters as &$value) {
            if (is_array($value)) {
                sort($value, SORT_STRING | SORT_FLAG_CASE);
            }
        }

        unset($value);
    }

    private function isFilterActive(string $valueSlug, array $filters): bool
    {
        foreach ($filters as $value) {
            if (is_string($value) && $valueSlug === $value)
                return true;

            else if (is_array($value) && in_array($valueSlug, $value, true))
                return true;
        }
        return false;
    }
}
