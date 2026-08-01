<?php

namespace App\Services\Helpers\Import;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Services\Helpers\Import\SpreadSheetReadException;
use App\Services\Helpers\Import\SpreadSheetReadResult;
use Illuminate\Support\Collection;
use Throwable;

class SpreadSheetReaderService
{
    /**
     * Read a spreadsheet from storage and return its structured contents.
     *
     * @param string $filePath
     * @param string $disk
     * @return SpreadSheetReadResult
     * @throws SpreadSheetReadException
     */
    public function read(string $filePath, string $disk = 'r2'): SpreadSheetReadResult
    {
        if (!Storage::disk($disk)->exists($filePath)) {
            throw new SpreadSheetReadException('Uploaded file could not be found on storage.', 404);
        }

        $temporaryLocalPath = tempnam(sys_get_temp_dir(), 'spreadsheet_import_');

        try {
            $stream = Storage::disk($disk)->readStream($filePath);
            file_put_contents($temporaryLocalPath, stream_get_contents($stream));

            if (is_resource($stream)) {
                fclose($stream);
            }

            $reader = IOFactory::createReaderForFile($temporaryLocalPath);
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($temporaryLocalPath);
            $sheet = $spreadsheet->getActiveSheet();

            $rows = collect($sheet->toArray(null, true, true, false))
                ->map(fn(array $row) => collect($row));

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (Throwable $e) {
            throw new SpreadSheetReadException('Unable to read spreadsheet: ' . $e->getMessage(), 422, $e);
        } finally {
            if (file_exists($temporaryLocalPath)) {
                unlink($temporaryLocalPath);
            }
        }

        if ($rows === null || $rows->isEmpty()) {
            throw new SpreadSheetReadException('The uploaded file contains no data rows.', 422);
        }

        $header = $rows->first()->map(fn($value) => strtolower(trim((string) $value)))->toArray();
        $dataRows = $rows->slice(1)->values();

        return new SpreadSheetReadResult($header, $dataRows, $dataRows->count());
    }

    public function deleteIfExists(string $filePath, string $disk = 'r2'): void
    {
        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }
    }
}
