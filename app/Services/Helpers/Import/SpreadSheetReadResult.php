<?php

namespace App\Services\Helpers\Import;

use Illuminate\Support\Collection;

class SpreadSheetReadResult
{
    public function __construct(
        public readonly array $header,
        public readonly Collection $dataRows,
        public readonly int $total,
    ) {}
}
