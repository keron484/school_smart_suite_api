<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AddTeacherAvailabilityRequest;
use App\Http\Requests\Teacher\BulkUpdateTeacherAvailabilitySlotsRequest;
use App\Services\ApiResponseService;
use App\Services\Teacher\TeacherPreferedTeachingTimeService;
use Illuminate\Http\Request;
use Exception;

class TeacherPreferedTeachingTimeController extends Controller
{
    protected TeacherPreferedTeachingTimeService $instructorAvaliabilityService;
    public function __construct(TeacherPreferedTeachingTimeService $instructorAvaliabilityService)
    {
        $this->instructorAvaliabilityService = $instructorAvaliabilityService;
    }
    public function createInstructorAvailability(AddTeacherAvailabilityRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        try {
            $createInstructorAvailability = $this->instructorAvaliabilityService->createInstructorAvailability($request->instructor_availability, $currentSchool);
            return ApiResponseService::success("Instructor Avialability Created Sucessfully", $createInstructorAvailability, null, 201);
        } catch (Exception $e) {
            return ApiResponseService::error($e->getMessage(), null,  500);
        }
    }
    public function createAvailabilityByOtherSlots(Request $request)
    {
        try {
            $currentSchool = $request->attributes->get('currentSchool');
            $targetAvailabilityId = $request->route('targetAvailabilityId');
            $availabilityId = $request->route('availabilityId');
            $this->instructorAvaliabilityService->createAvialabilityByOtherSlots(
                $targetAvailabilityId,
                $availabilityId,
                $currentSchool
            );
            return ApiResponseService::success("Instructor Avialability Created Sucessfully", null, null, 201);
        } catch (Exception $e) {
            return ApiResponseService::error($e->getMessage(), null, 500);
        }
    }
    public function bulkUpdateInstructorAvialabililty(BulkUpdateTeacherAvailabilitySlotsRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        try {
            $updateInstructorAvailability = $this->instructorAvaliabilityService->bulkUpdateInstructorAvailability($request->instructor_availability, $currentSchool);
            return ApiResponseService::success('Instructor Avialabilty Updated Succefully', $updateInstructorAvailability, null, 200);
        } catch (Exception $e) {
            return ApiResponseService::error($e->getMessage(), null, $e->getCode() ?: 500);
        }
    }
    public function getSchoolSemestersByTeacherSpecialtyPreference(Request $request, string $teacherId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $schoolSemesters = $this->instructorAvaliabilityService->getSchoolSemestersByTeacherSpecialtyPreference($currentSchool, $teacherId);
        return ApiResponseService::success("School Semesters Fetched Successfully", $schoolSemesters, null, 200);
    }
    public function deleteAvailabilitySlots(Request $request)
    {
        $teacherId = $request->route('teacherId');
        $availabilityId = $request->route('availabilityId');
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteSlots = $this->instructorAvaliabilityService->deleteAvailabilitySlots($availabilityId, $currentSchool, $teacherId);
        return ApiResponseService::success("All Availability Slots Deleted Successfully", $deleteSlots, null, 200);
    }
    public function getInstructorAvailabilities(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getAllInstructorAvailability = $this->instructorAvaliabilityService->getInstructorAvailabilities($currentSchool);
        return ApiResponseService::success("teacher availability data fetched successfully", $getAllInstructorAvailability, null, 200);
    }
    public function getInstructorAvailabilitesByTeacher(Request $request, string  $teacherId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $getMyInstructorAvailability = $this->instructorAvaliabilityService->getInstructorAvailabilitesByTeacher($currentSchool, $teacherId);
        return ApiResponseService::success('Instructor Availabilty Fetched Sucessfully', $getMyInstructorAvailability, null, 200);
    }
    public function getInstructorAvailabilityDetails(Request $request, string $availabilityId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $avaialbilityDetails = $this->instructorAvaliabilityService->getInstructorAvailabilityDetails($currentSchool, $availabilityId);
        return ApiResponseService::success("Instructor Availability Details Fetched Successfully", $avaialbilityDetails, null, 200);
    }
    public function getAvailabilitySlotsByTeacherAvailability(Request $request, string $availabilityId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $slots = $this->instructorAvaliabilityService->getAvailabilitySlots($currentSchool, $availabilityId);
        return ApiResponseService::success("Availability Slots By Teacher Fetched Successfully", $slots, null, 200);
    }
}
