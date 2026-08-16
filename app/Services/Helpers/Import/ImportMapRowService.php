<?php

namespace App\Services\Helpers\Import;

class ImportMapRowService
{
    public  function mapRow(
        array $row,
        array $columnIndexes
    ): array {
        $payload = [];
        foreach ($columnIndexes['standardFields'] ?? [] as $field => $index) {
            $value = $row[$index] ?? null;

            $payload[$field] = is_string($value)
                ? trim($value)
                : $value;
        }
        foreach (
            $columnIndexes['repeatableGroups'] ?? []
            as $groupName => $instances
        ) {
            $payload[$groupName] = [];

            foreach ($instances as $instance) {
                $mappedInstance = [];

                foreach ($instance as $field => $index) {
                    $value = $row[$index] ?? null;

                    $mappedInstance[$field] = is_string($value)
                        ? trim($value)
                        : $value;
                }

                if ($this->hasValue($mappedInstance)) {
                    $payload[$groupName][] = $mappedInstance;
                }
            }
        }

        return $payload;
    }

    private function hasValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return true;
        }

        return false;
    }
}
