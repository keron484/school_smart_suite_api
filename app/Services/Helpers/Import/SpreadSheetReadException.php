<?php

namespace App\Services\Helpers\Import;
use Exception;
use Throwable;
class SpreadSheetReadException extends Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
