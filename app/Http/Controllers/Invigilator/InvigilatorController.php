<?php

namespace App\Http\Controllers\Invigilator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invigilator\AssignExamInvigilator;
use App\Http\Requests\Invigilator\RemoveInvigilatorRequest;
use App\Services\ApiResponseService;
use App\Services\Invigilator\InvigilatorService;
use Illuminate\Http\Request;

class InvigilatorController extends Controller
{
    protected InvigilatorService $invigilatorService;
    public function __construct(InvigilatorService $invigilatorService)
    {
        $this->invigilatorService = $invigilatorService;
    }

    public function assignExamInvigilator(AssignExamInvigilator $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $assignInvigilator = $this->invigilatorService->assignExamInvigilator($currentSchool, $request->validated());
        return ApiResponseService::success("Invigilators Assigned Successfully", $assignInvigilator, null, 201);
    }

    public function getAssignedInvigilators(Request $request, string $examId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $assignedInvigs = $this->invigilatorService->getAssignedInvigilatorsExamId($currentSchool, $examId);
        return ApiResponseService::success("Assigned invigilators fetched Successfully", $assignedInvigs, null, 200);
    }

    public function removeAssignedInvigilators(RemoveInvigilatorRequest $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $removeInvigilator = $this->invigilatorService->removeExamInvigilators($currentSchool, $request->validated());
        return ApiResponseService::success("Assigned Invigilators Removed Successfully", $removeInvigilator, null, 200);
    }

    public function getPotentialInvigilators(Request $request, string $examId)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $invigs = $this->invigilatorService->getPotentialInvigilators($currentSchool, $examId);
        return ApiResponseService::success("Potential Invigilators Fetched Successfully", $invigs, null, 200);
    }
}
