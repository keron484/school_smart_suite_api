<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Examtype;

class test extends Seeder
{
    public function run(): void
    {
        $exams = [
            [
                'exam_name' => 'First Semester CA (Continuous Assessment)',
                'description' => 'Evaluates ongoing academic performance (tests, assignments, attendance) during the first half of the session. Typically worth 30–40% of the total semester grade.'
            ],
            [
                'exam_name' => 'First Semester Exam',
                'description' => 'The major end-of-term assessment covering all first-semester course topics. Typically accounts for 60–70% of the final semester grade.'
            ],
            [
                'exam_name' => 'First Semester Resit',
                'description' => 'A supplementary assessment for students seeking to clear failed courses or improve low grades from the first semester.'
            ],
            [
                'exam_name' => 'Second Semester CA (Continuous Assessment)',
                'description' => 'Tracks continuous coursework and practical assessments throughout the second term. Contributes 30–40% toward the final semester evaluation.'
            ],
            [
                'exam_name' => 'Second Semester Exam',
                'description' => 'The final cumulative exam of the academic session covering second-semester material. Carries 60–70% of the semester grade to determine session completion.'
            ],
            [
                'exam_name' => 'Second Semester Resit',
                'description' => 'A second-chance assessment allowing students to remediate failed second-semester courses before the next academic year.'
            ]
        ];

        foreach ($exams as $examData) {
            $exam = Examtype::where("exam_name", $examData['exam_name'])->first();

            if ($exam) {
                $exam->update([
                    'description' => $examData['description']
                ]);
            }
        }
    }
}
