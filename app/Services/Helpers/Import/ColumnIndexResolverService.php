<?php

namespace App\Services\Helpers\Import;


class ColumnIndexResolverService
{
    public static function resolve(
        array $header,
        array $mapping,
        array $required = ['email', 'full_names', 'first_name', 'last_name', 'phone'],
        array $optional = ['address', 'gender']
    ): ?array {
        $header = array_map(static fn($h) => strtolower(trim((string) $h)), $header);
        $indexes = [];


        foreach ($required as $key) {
            $columnName = strtolower(trim((string) ($mapping[$key] ?? '')));

            if ($columnName === '') {
                return null;
            }

            $index = array_search($columnName, $header, false);

            if ($index === false) {
                return null;
            }

            $indexes[$key] = $index;
        }

        foreach ($optional as $key) {
            if (empty($mapping[$key])) {
                continue;
            }

            $columnName = strtolower(trim((string) $mapping[$key]));
            $index = array_search($columnName, $header, false);

            if ($index !== false) {
                $indexes[$key] = $index;
            }
        }

        return $indexes;
    }
}
