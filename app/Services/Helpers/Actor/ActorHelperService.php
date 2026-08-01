<?php

namespace App\Services\Helpers\Actor;

class ActorHelperService
{
    public function generateUsername(string $name, string $model, string $column = 'username'): string
    {
        $normalized = strtolower(trim($name));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        $base = match (true) {
            empty($parts)       => 'user',
            count($parts) === 1 => $parts[0],
            default             => $parts[0][0] . end($parts),
        };

        $base = strlen($base) < 3 ? str_pad($base, 3, '0') : $base;

        $base = substr($base, 0, 20);

        $username = $base;
        $counter = 1;
        while ($model::where($column, $username)->exists()) {
            $username = $base . $counter++;
        }

        return $username;
    }
    public function generateRandomPassword($length = 10): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
