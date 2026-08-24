<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherSpecialty\AssignTeacherRequest;
use App\Http\Requests\TeacherSpecialty\ImportTeacherSpecialtyRequest;
use App\Http\Requests\TeacherSpecialty\RemoveTeacherRequest;
use App\Jobs\Teacher\TeacherSpecialtyImportJob;
use App\Services\ApiResponseService;
use App\Services\Teacher\TeacherSpecialtyService;
use App\Models\Job\SystemJobDetail;
use App\Models\Job\SystemJob;
use App\Models\Teacher\TeacherSpecialty;
use App\Models\Job\SystemJobCategory;
use Illuminate\Http\Request;

class TeacherSpecialtyController extends Controller
{
    protected TeacherSpecialtyService $teacherSpecialtyService;
    public function __construct(TeacherSpecialtyService $teacherSpecialtyService)
    {
        $this->teacherSpecialtyService = $teacherSpecialtyService;
    }

    public function getTeacherSpecialtyByTeacherId(Request $request, string $teacherId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $specialties = $this->teacherSpecialtyService->getTeacherSpecialtyTeacherId($currentSchool, $teacherId);
        return ApiResponseService::success("Teacher Specialties Fetched Successfully", $specialties, null, 200);
    }

    public function assignTeachers(AssignTeacherRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $assignTeacher = $this->teacherSpecialtyService->assignTeachers($currentSchool, $request->validated());
        return ApiResponseService::success("Teacher Assigned Successfully", $assignTeacher, null, 200);
    }

    public function removeAssignedTeachers(RemoveTeacherRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $removeAssignedTeacher = $this->teacherSpecialtyService->removeTeachers($currentSchool, $request->validated());
        return ApiResponseService::success("Teacher Removed Successfully", $removeAssignedTeacher, null, 200);
    }

    public function getAssignableTeachers(Request $request, string $specialtyId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $assignableTeachers = $this->teacherSpecialtyService->getAssignableTeachers($currentSchool, $specialtyId);
        return ApiResponseService::success("Assignable Teachers Fetched Successfully", $assignableTeachers, null, 200);
    }

    public function importTeacherSpecialty(ImportTeacherSpecialtyRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'teacher')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/teachers/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'teacher_specialty_import',
            'context_type'      => TeacherSpecialty::class,
            'stage'             => 'Queued',
            'status'            => 'queued',
            'context_id'        => $currentSchool->id,
            'initiated_by_id'   => $authUser->id,
            'category_id'       => $category->id,
            'initiated_by_type' => $authUser::class,
            'queue'             => 'database',
            'started_at'        => now(),
        ]);

        SystemJobDetail::create([
            'job_id'           => $systemJob->id,
            'school_branch_id' => $currentSchool->id,
            'input'            => [
                'file_path' => $filePath,
                'mapping'       => $payload['mapping'],
                'original'  => $payload,
            ],
            'summary'          => null,
            'result'           => null,
            'metadata'         => [
                'last_broadcast_progress' => 0,
                'last_broadcast_status'   => null,
                'last_broadcast_at'       => null,
            ],
        ]);

        TeacherSpecialtyImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Teacher Specialty Importation Process Initiated Successfully',
            null,
            null,
            200
        );
    }

    protected function resolveUser()
    {
        foreach (['student', 'teacher', 'schooladmin'] as $guard) {
            $user = request()->user($guard);
            if ($user !== null) {
                return $user;
            }
        }
        return null;
    }
}
