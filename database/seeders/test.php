<?php

namespace Database\Seeders;

use App\Models\Gender;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class test extends Seeder
{
    public function run(): void
    {
        $genders = Gender::all()->pluck('id')->toArray();
        $teachers = Teacher::all();
        foreach($teachers as $teacher){
             $teacher->update([
                 'gender_id' => Arr::random($genders)
             ]);
        }
    }
}
