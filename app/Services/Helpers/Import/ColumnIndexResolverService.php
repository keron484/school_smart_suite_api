<?php

namespace App\Services\Helpers\Import;


class ColumnIndexResolverService
{
    public static function resolve(
        array $header,
        array $mapping
    ): ?array {
        $normalizedHeader = array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            $header
        );

        $resolved = [
            'standardFields' => [],
            'repeatableGroups' => [],
        ];

        /*
         * Standard fields
         */
        foreach ($mapping['standardFields'] ?? [] as $field => $columnName) {
            if (self::isEmpty($columnName)) {
                continue;
            }

            $index = self::findColumnIndex(
                $normalizedHeader,
                $columnName
            );

            if ($index === null) {
                return null;
            }

            $resolved['standardFields'][$field] = $index;
        }

        /*
         * Repeatable groups
         */
        foreach ($mapping['repeatableGroups'] ?? [] as $groupName => $instances) {
            $resolved['repeatableGroups'][$groupName] = [];

            foreach ($instances as $instance) {
                $resolvedInstance = [];

                foreach ($instance as $field => $columnName) {
                    if (self::isEmpty($columnName)) {
                        continue;
                    }

                    $index = self::findColumnIndex(
                        $normalizedHeader,
                        $columnName
                    );

                    if ($index === null) {
                        return null;
                    }

                    $resolvedInstance[$field] = $index;
                }

                if ($resolvedInstance !== []) {
                    $resolved['repeatableGroups'][$groupName][] = $resolvedInstance;
                }
            }
        }

        return $resolved;
    }

    private static function findColumnIndex(
        array $header,
        mixed $columnName
    ): ?int {
        $columnName = strtolower(trim((string) $columnName));

        $index = array_search(
            $columnName,
            $header,
            true
        );

        return $index === false ? null : $index;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null
            || trim((string) $value) === '';
    }
}
