<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Semester;
use Illuminate\Support\Str;
class ExamTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createExamType();
    }
    private function createExamType(): void
    {
        $timestamp = now();
        $filePath = public_path("data/exam_type.csv");
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("CSV file not found or not readable!");
        }

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);

            $exam_type = [];

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $uuid = Str::uuid()->toString();
                if (count($data) >= 2) {
                    $exam_type[] = [
                        'id' => $uuid,
                        'exam_name' => $data[1],
                        "program_name" => $data[2],
                        "semester" => $data[3],
                        "type" => $data[4],
                        "description" =>  $data[6],
                        'semester_id' => Semester::where('count', $data[5])->first()->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp
                    ];
                }
            }

            fclose($handle);


            if (!empty($exam_type)) {
                DB::table('exam_types')->insert($exam_type);
            } else {
                Log::warning('No Exam types to insert.');
            }
        }
    }
}
