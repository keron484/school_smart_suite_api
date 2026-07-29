<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Job\SystemJobCategory;

class JobCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                "name" => "Teacher",
                "description" => "Manage teacher profiles, assignments, and classroom activities",
                "status" => "active"
            ],
            [
                "name" => "Student",
                "description" => "Manage student enrollment, academic records, and progress tracking",
                "status" => "active"
            ],
            [
                "name" => "School Admin",
                "description" => "Oversee school operations, staff management, and institutional settings",
                "status" => "active"
            ],
            [
                "name" => "Department",
                "description" => "Manage academic departments, course offerings, and departmental resources",
                "status" => "active"
            ],
            [
                "name" => "Specialty",
                "description" => "Handle specialized programs, electives, and subject-specific offerings",
                "status" => "active"
            ],
            [
                "name" => "Attendance",
                "description" => "Track student attendance, generate reports, and manage absence records",
                "status" => "active"
            ],
            [
                "name" => "Examination",
                "description" => "Manage exam schedules, grading, results processing, and academic performance",
                "status" => "active"
            ],
            [
                "name" => "Finance",
                "description" => "Handle school finances, fee collection, budgeting, and accounting",
                "status" => "active"
            ],
            [
                "name" => "Human Resources",
                "description" => "Manage staff recruitment, payroll, benefits, and employee records",
                "status" => "active"
            ],
            [
                "name" => "Library",
                "description" => "Manage library resources, book cataloging, and student borrowing",
                "status" => "active"
            ],
            [
                "name" => "Transportation",
                "description" => "Manage school transportation, bus routes, and student pick-up/drop-off",
                "status" => "active"
            ],
            [
                "name" => "Health Services",
                "description" => "Manage student health records, clinic services, and medical emergencies",
                "status" => "active"
            ],
            [
                "name" => "Sports and Athletics",
                "description" => "Organize sports activities, manage teams, and coordinate athletic events",
                "status" => "active"
            ],
            [
                "name" => "Cafeteria",
                "description" => "Manage meal planning, food services, and cafeteria operations",
                "status" => "active"
            ],
            [
                "name" => "IT Support",
                "description" => "Manage school technology infrastructure, devices, and technical support",
                "status" => "active"
            ],
            [
                "name" => "Security",
                "description" => "Manage school security, access control, and campus safety",
                "status" => "active"
            ],
            [
                "name" => "Maintenance",
                "description" => "Manage school facilities, building maintenance, and repair services",
                "status" => "active"
            ],
            [
                "name" => "Parent Relations",
                "description" => "Manage parent communication, meetings, and community engagement",
                "status" => "active"
            ],
            [
                "name" => "Guidance and Counseling",
                "description" => "Provide student counseling, career guidance, and mental health support",
                "status" => "active"
            ],
            [
                "name" => "Alumni",
                "description" => "Manage alumni relations, reunions, and graduate networking",
                "status" => "active"
            ],
            [
                "name" => "Admissions",
                "description" => "Manage student applications, enrollment process, and admissions criteria",
                "status" => "active"
            ],
            [
                "name" => "Curriculum Development",
                "description" => "Develop course materials, academic programs, and educational content",
                "status" => "active"
            ],
            [
                "name" => "Quality Assurance",
                "description" => "Monitor educational standards, conduct evaluations, and ensure quality compliance",
                "status" => "active"
            ],
            [
                "name" => "Research",
                "description" => "Manage research projects, publications, and academic collaborations",
                "status" => "active"
            ],
            [
                "name" => "Public Relations",
                "description" => "Manage school communications, media relations, and public image",
                "status" => "active"
            ],
        ];

        foreach ($data as $category) {
            SystemJobCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'status' => $category['status']
                ]
            );
        }
    }
}
