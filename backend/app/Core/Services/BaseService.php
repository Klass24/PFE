<?php

namespace App\Core\Services;

abstract class BaseService
{
    /**
     * Filtre un tableau de données en retirant les clés nulles optionnelles.
     */
    protected function filterData(array $data, array $nullable = []): array
    {
        return array_filter($data, function ($value, $key) use ($nullable) {
            return $value !== null || in_array($key, $nullable);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
