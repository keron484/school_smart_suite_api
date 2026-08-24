<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Exceptions\AppException;
use App\Services\ApiResponseService;
use App\Events\Actions\AdminActionEvent;
use App\Events\Actions\StudentActionEvent;

class StudentService
{
    public function getStudents(object $currentSchool)
    {
        $students = Student::where('school_branch_id', $currentSchool->id)->with([
            'guardian',
            'specialty.level',
            'specialty.department',
            'studentBatch',
            'gender',
            'relationship'
        ])
            ->where("dropout_status", false)->get();
        return $students;
    }
    public function deleteStudent(string $studentId, object $currentSchool, object $authAdmin)
    {
        $studentExists = Student::Where("school_branch_id", $currentSchool->id)->find($studentId);
        if (!$studentExists) {
            return ApiResponseService::error("Student Not found", null, 404);
        }
        $studentExists->delete();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.student.delete.student"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "studentManagement",
                "action" => "student.deleted",
                "authAdmin" => $authAdmin,
                "data" => $studentExists,
                "message" => "Student Deleted",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$studentExists->id],
            'feature'      => 'studentDelete',
            'message'      => 'student Account Deleted',
            'data'         => $studentExists,
        ]);
        return $studentExists;
    }
    public function updateStudent(string $studentId, object $currentSchool, array $data, $authAdmin)
    {
        $studentExists = Student::Where("school_branch_id", $currentSchool->id)->find($studentId);
        if (!$studentExists) {
            return ApiResponseService::error("Student Not found", null, 404);
        }

        $filteredData = array_filter($data);
        $studentExists->update($filteredData);
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.student.update"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "studentManagement",
                "action" => "student.updated",
                "authAdmin" => $authAdmin,
                "data" => $studentExists,
                "message" => "Student Updated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$studentExists->id],
            'feature'      => 'studentUpdate',
            'message'      => 'student Account Updated',
            'data'         => $studentExists,
        ]);
        return $studentExists;
    }
    public function studentDetails(string $studentId, object $currentSchool)
    {
        $studentDetails = Student::where("school_branch_id", $currentSchool->id)
            ->with([
                'guardian',
                'specialty',
                'level',
                'gender',
                'studentSource',
                'studentBatch',
                'studentParentRelationship'
            ])
            ->find($studentId);
        return $studentDetails;
    }
    public function deactivateStudentAccount(string $studentId, object $currentSchool, $authAdmin)
    {
        $student = Student::where("school_branch_id", $currentSchool->id)->findOrFail($studentId);
        $student->status = 'inactive';
        $student->save();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.student.deactivate"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "studentManagement",
                "authAdmin" => $authAdmin,
                "data" => $student,
                "action" => "student.deactivated",
                "message" => "Student Account Deactivated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$student->id],
            'feature'      => 'studentDeactivate',
            'message'      => 'student Account Deactivated',
            'data'         => $student,
        ]);
        return $student;
    }
    public function activateStudentAccount(string $studentId, object $currentSchool, $authAdmin)
    {
        $student = Student::where("school_branch_id", $currentSchool->id)->findOrFail($studentId);
        $student->status = 'active';
        $student->save();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.student.activate"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "studentManagement",
                "authAdmin" => $authAdmin,
                "action" => "student.activated",
                "data" => $student,
                "message" => "Student Account Activated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$student->id],
            'feature'      => 'studentDeactivate',
            'message'      => 'student Account Activated',
            'data'         => $student,
        ]);
        return $student;
    }
    public function markStudentAsDropout(string $studentId, object $currentSchool, $reason, $authAdmin)
    {
        $student = Student::where("school_branch_id", $currentSchool->id)->findOrFail($studentId);

        $student->dropout_status = true;
        $student->save();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.student.mark.student.as.dropout"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "studentManagement",
                "authAdmin" => $authAdmin,
                "action" => "student.markedAsDropout",
                "data" => $student,
                "message" => "Student Marked As Drop Out",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$student->id],
            'feature'      => 'studentDropout',
            'message'      => 'student Marked As Drop out',
            'data'         => $student,
        ]);
        return $student;
    }
    public function bulkReinstateDropOutStudent(array $studentDropoutList, object $currentSchool, $authAdmin)
    {
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($studentDropoutList as $studentDropout) {
                $student = Student::where("school_branch_id", $currentSchool->id)
                    ->findOrFail($studentDropout['student_id']);
                $student->dropout_status = false;
                $student->save();
                $studentIds[] = $student->id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.reinstate.dropout.student"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "studentManagement",
                    "action" => "student.reinstated",
                    "authAdmin" => $authAdmin,
                    "data" => $studentDropoutList,
                    "message" => "Drop Out Student Reinstated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentDropout',
                'message'      => 'Drop Out Student Reinstated',
                'data'         => $student,
            ]);
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function getAllDropoutStudents(object $currentSchool)
    {
        try {
            $dropoutStudents = Student::where('school_branch_id', $currentSchool->id)
                ->with([
                    'guardian',
                    'specialty.level',
                    'specialty.department',
                    'studentBatch',
                    'gender',
                    'relationship'
                ])
                ->where('dropout_status', true)
                ->get();

            if ($dropoutStudents->isEmpty()) {
                throw new AppException(
                    "No dropout students were found for this school branch.",
                    404,
                    "No Dropout Students Found",
                    "There are currently no students marked with a dropout status in the system for your school.",
                    null
                );
            }

            return $dropoutStudents;
        } catch (AppException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AppException(
                "An unexpected error occurred while retrieving dropout students.",
                500,
                "Internal Server Error",
                "A server-side issue prevented the list of dropout students from being retrieved successfully.",
                null
            );
        }
    }
    public function reinstateDropoutStudent(string $studentDropoutId, object $currentSchool, $authAdmin)
    {
        $dropoutStudent = Student::where('school_branch_id', $currentSchool->id)->find($studentDropoutId);
        if (!$dropoutStudent) {
            return ApiResponseService::error("Dropout Student Not found", null, 404);
        }
        $dropoutStudent->dropout_status = false;
        $dropoutStudent->save();
        AdminActionEvent::dispatch(
            [
                "permissions" =>  ["schoolAdmin.student.reinstate.dropout.student"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" =>  $currentSchool->id,
                "feature" => "studentManagement",
                "action" => "student.reinstated",
                "authAdmin" => $authAdmin,
                "data" => $dropoutStudent,
                "message" => "Drop Out Student Reinstated",
            ]
        );
        StudentActionEvent::dispatch([
            'schoolBranch' => $currentSchool->id,
            'studentIds'   => [$dropoutStudent->id],
            'feature'      => 'studentDropout',
            'message'      => 'Drop Out Student Reinstated',
            'data'         => $dropoutStudent,
        ]);
        return $dropoutStudent;
    }
    public function bulkMarkStudentAsDropOut(array $studentDropdoutList, object $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($studentDropdoutList as $studentDropout) {
                $student = Student::where("school_branch_id", $currentSchool->id)->findOrFail($studentDropout['student_id']);
                $student->dropout_status = true;
                $result[] = $student;
                $studentIds = $student->id;
                $student->save();
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.mark.student.as.dropout"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "studentManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $student,
                    "message" => "Student Marked As Drop Out",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentDropout',
                'message'      => 'student Marked As Drop out',
                'data'         => $student,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkDeleteStudent(array $studentIds, object $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($studentIds as $studentId) {
                $student = Student::where("school_branch_id", $currentSchool->id)->findOrFail($studentId['student_id']);
                $student->delete();
                $result[] = $student;
                $studentIds[] = $student->id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.delete.student"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "studentManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Student Deleted",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentDelete',
                'message'      => 'Student Account Deleted',
                'data'         => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkUpdateStudent(array $updateData, object $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($updateData as $data) {
                $student = Student::where("school_branch_id", $currentSchool->id)->find($data['student_id']);
                $filteredData = array_filter($data);
                $student->update($filteredData);
                $result[] = $student;
                $studentIds[] = $student->id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "action" => "student.updated",
                    "feature" => "studentManagement",
                    "authAdmin" => $authAdmin,
                    "data" => $updateData,
                    "message" => "Student Updated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentUpdate',
                'message'      => 'Student Account Updated',
                'data'         => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkActivateStudent(array $studentIds, object $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($studentIds as $studentId) {
                $student = Student::where("school_branch_id", $currentSchool->id)->findOrFail($studentId['student_id']);
                $student->status =  'active';
                $student->save();
                $result[] = $student;
                $studentIds[] = $student->id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.activate"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "studentManagement",
                    "action" => "student.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Student Account Activated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentActivate',
                'message'      => 'Student Account Activated',
                'data'         => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkDeactivateStudent($studentIds, $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($studentIds as $studentId) {
                $student = Student::where("school_branch_id", $currentSchool->id)
                    ->findOrFail($studentId['student_id']);
                $student->status =  'inactive';
                $student->save();
                $result[] = $student;
                $studentIds[] = $studentIds;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.deactivate"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "studentManagement",
                    "action" => "student.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Student Account Deactivated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentDeactivate',
                'message'      => 'Student Account Deactivated',
                'data'         => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function bulkReinstateStudent($dropOutIds, $currentSchool, $authAdmin)
    {
        $result = [];
        $studentIds = [];
        try {
            DB::beginTransaction();
            foreach ($dropOutIds as $dropOutId) {
                $studentDropout = Student::where("school_branch_id", $currentSchool->id)
                    ->findOrFail($dropOutId['student_id']);
                $studentDropout->dropout_status = false;
                $studentDropout->save();
                $result[] = $studentDropout;
                $studentIds[] = $studentDropout->id;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" =>  ["schoolAdmin.student.reinstate.dropout.student"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" =>  $currentSchool->id,
                    "feature" => "studentManagement",
                    "action" => "student.reinstated",
                    "authAdmin" => $authAdmin,
                    "data" => $dropOutIds,
                    "message" => "Drop Out Student Reinstated",
                ]
            );
            StudentActionEvent::dispatch([
                'schoolBranch' => $currentSchool->id,
                'studentIds'   => $studentIds,
                'feature'      => 'studentDropout',
                'message'      => 'Student Dropout Reinstated',
                'data'         => $result,
            ]);
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function uploadProfilePicture($request, $authStudent)
    {
        $student = Student::find($authStudent->id);
        if (!$student) {
            return ApiResponseService::error("Student Not Found", null, 400);
        }
        try {
            DB::transaction(function () use ($request, $student) {

                if ($student->profile_picture) {
                    Storage::disk('public')->delete('StudentAvatars/' . $student->profile_picture);
                }
                $profilePicture = $request->file('profile_picture');
                $fileName = time() . '.' . $profilePicture->getClientOriginalExtension();
                $profilePicture->storeAs('public/StudentAvatars', $fileName);

                $student->profile_picture = $fileName;
                $student->save();
            });
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function deleteProfilePicture($authStudent)
    {
        try {
            $student = Student::find($authStudent->id);
            if (!$student) {
                return ApiResponseService::error("Student Not found", null, 400);
            }
            if (!$student->profile_picture) {
                return ApiResponseService::error("No Profile Picture to Delete {$student->name}", null, 400);
            }
            Storage::disk('public')->delete('SchoolAdminAvatars/' . $student->profile_picture);

            $student->profile_picture = null;
            $student->save();

            return $student;
        } catch (Throwable $e) {
            throw $e;
        }
    }
    public function getStudentProfileDetails($currentSchool, $studentId)
    {
        $student = Student::where("school_branch_id", $currentSchool->id)
            ->with(['department', 'specialty', 'guardian', 'level', 'schoolbranches'])
            ->find($studentId);
        return $student;
    }
}
