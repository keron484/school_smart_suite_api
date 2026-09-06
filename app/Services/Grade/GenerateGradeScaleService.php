<?php

namespace App\Services\Grade;

use App\Models\GradeScale\Grade;

class GenerateGradeScaleService
{
    public function generateGradeScale(array $payload)
    {
        $maxScore = (float) $payload['max_score'];
        $examType = $payload['exam_type'];
        $letterGrades = Grade::all();

        $performanceRemarks = [
            'A+' => ['Outstanding', 'Exceptional', 'Brilliant', 'Superb', 'Exemplary'],
            'A'  => ['Excellent', 'Outstanding', 'Exceptional', 'Brilliant'],
            'A-' => ['Excellent', 'Great', 'Outstanding', 'Very Good'],
            'B+' => ['Great', 'Very Good', 'Impressive', 'Commendable'],
            'B'  => ['Good', 'Great', 'Solid', 'Satisfactory'],
            'B-' => ['Good', 'Satisfactory', 'Adequate', 'Fair'],
            'C+' => ['Satisfactory', 'Adequate', 'Fair', 'Acceptable'],
            'C'  => ['Adequate', 'Fair', 'Pass', 'Satisfactory'],
            'C-' => ['Pass', 'Adequate', 'Below Average', 'Mediocre'],
            'D+' => ['Below Average', 'Weak', 'Needs Improvement', 'Barely Passing'],
            'D'  => ['Weak', 'Poor', 'Needs Improvement', 'Unsatisfactory'],
            'D-' => ['Poor', 'Unsatisfactory', 'Very Weak', 'Concerning'],
            'F'  => ['Fail', 'Unsatisfactory', 'Poor', 'Needs Significant Improvement', 'Reconsider'],
        ];

        $caResitResult = ['high_resit_potential', 'low_resit_potential'];
        $examResitResult = ['resit', 'no_resit'];
        $resultStatuses = ['passed', 'failed'];
        $result = [];

        if ($letterGrades->isEmpty()) {
            return $result;
        }

        $gradeMinPercents = [
            'A+' => 90,
            'A' => 85,
            'A-' => 80,
            'B+' => 75,
            'B' => 70,
            'B-' => 65,
            'C+' => 60,
            'C' => 55,
            'C-' => 50,
            'D+' => 45,
            'D'  => 42,
            'D-' => 40,
            'F'  => 0,
        ];

        $gradePoints = [
            'A+' => 4.00,
            'A' => 3.90,
            'A-' => 3.70,
            'B+' => 3.30,
            'B' => 3.00,
            'B-' => 2.70,
            'C+' => 2.30,
            'C' => 2.00,
            'C-' => 1.70,
            'D+' => 1.30,
            'D' => 1.00,
            'D-' => 0.70,
            'F' => 0.00,
        ];

        $passThreshold = $maxScore * 0.5;
        $lettersFromDb = $letterGrades->keyBy('letter_grade');
        $orderedLetters = array_keys($gradeMinPercents);
        $ranges = [];
        $previousMin = null;
        foreach ($orderedLetters as $grade) {
            if (!isset($lettersFromDb[$grade])) {
                continue;
            }

            $min = $grade === 'F'
                ? 0.00
                : $this->truncateTwoDecimals(($gradeMinPercents[$grade] / 100) * $maxScore);

            $max = $previousMin === null
                ? $this->truncateTwoDecimals($maxScore)
                : $this->truncateTwoDecimals($previousMin - 0.01);

            if ($max < $min) {
                $max = $min;
            }

            $ranges[$grade] = ['min' => $min, 'max' => $max];
            $previousMin = $min;
        }

        foreach ($orderedLetters as $grade) {
            if (!isset($ranges[$grade])) {
                continue;
            }

            $letterGrade = $lettersFromDb[$grade];
            $minScore = $ranges[$grade]['min'];
            $maxScoreForGrade = $ranges[$grade]['max'];

            $currentGradeStatus = ($minScore >= $passThreshold) ? $resultStatuses[0] : $resultStatuses[1];
            $resitStatus = ($examType === 'ca')
                ? ($currentGradeStatus === 'failed' ? $caResitResult[0] : $caResitResult[1])
                : ($currentGradeStatus === 'failed' ? $examResitResult[0] : $examResitResult[1]);

            $remarksForGrade = $performanceRemarks[$grade] ?? $performanceRemarks['F'];
            $determinant = $remarksForGrade[array_rand($remarksForGrade)];

            $result[] = [
                'letter_grade_id' => $letterGrade->id,
                'letter_grade' => $letterGrade->letter_grade,
                'grade_points' => number_format($gradePoints[$grade], 2, '.', ''),
                'minimum_score' => number_format($minScore, 2, '.', ''),
                'maximum_score' => number_format($maxScoreForGrade, 2, '.', ''),
                'result' => $currentGradeStatus,
                'resit_result' => $resitStatus,
                'performance' => $determinant,
            ];
        }

        usort($result, function ($a, $b) {
            return (float) $b['minimum_score'] <=> (float) $a['minimum_score'];
        });

        return $result;
    }

    private function truncateTwoDecimals(float $value): float
    {
        return floor(($value * 100) + 0.0000001) / 100;
    }
}
