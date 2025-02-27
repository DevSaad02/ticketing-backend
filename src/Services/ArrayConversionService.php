<?php

namespace App\Services;

class ArrayConversionService
{
    /**
     * Convert a single model record to an array
     *
     * @param mixed $model Any model instance that has as_array() method
     * @return array
     */
    public function convertToArray($model): array
    {
        return $model->as_array();
    }

    /**
     * Convert a collection of model records to an array
     *
     * @param array $records Array of model instances
     * @return array
     */
    public function convertCollectionToArray(array $records): array
    {
        return array_map(fn($record) => $this->convertToArray($record), $records);
    }
}