<?php

namespace App\Http\Controllers\Hall;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hall\CreateHallRequest;
use App\Http\Requests\Hall\ImportHallRequest;
use App\Http\Requests\Hall\UpdateHallRequest;
use App\Jobs\Hall\HallImportJob;
use App\Models\Hall;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use App\Services\Hall\HallService;
use App\Models\Job\SystemJob;
use App\Models\Job\SystemJobCategory;
use App\Models\Job\SystemJobDetail;

class HallController extends Controller
{
    protected HallService $hallService;
    public function __construct(HallService $hallService)
    {
        $this->hallService = $hallService;
    }

    public function createHall(CreateHallRequest $request)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $createHall = $this->hallService->createHall($currentSchool, $request->validated(), $authAdmin);
        return ApiResponseService::success("Hall Created Successfully", $createHall, null, 200);
    }

    public function deleteHall(Request $request, string $hallId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $deleteHall = $this->hallService->deleteHall($currentSchool, $hallId, $authAdmin);
        return ApiResponseService::success("Hall Deleted Successfully", $deleteHall, null, 200);
    }

    public function updateHall(UpdateHallRequest $request, string $hallId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $updateHall = $this->hallService->updateHall($currentSchool, $request->validated(), $hallId, $authAdmin);
        return ApiResponseService::success("Hall Updated Successfully", $updateHall, null, 200);
    }

    public function getAllHalls(Request $request)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $halls = $this->hallService->getAllHalls($currentSchool);
        return ApiResponseService::success("Halls Fetched Successfully", $halls, null, 200);
    }

    public function getActiveHalls(Request $request)
    {
        $currentSchool = $request->attributes->get('currentSchool');
        $activeHalls = $this->hallService->getActiveHalls($currentSchool);
        return ApiResponseService::success("Active Halls Fetched Successfully", $activeHalls, null, 200);
    }

    public function activateHall(Request $request, string $hallId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $activateHall = $this->hallService->activateHall($currentSchool, $hallId, $authAdmin);
        return ApiResponseService::success("Hall Activated Successfully", $activateHall, null, 200);
    }

    public function deactivateHall(Request $request, string $hallId)
    {
        $authAdmin = $this->resolveUser();
        $currentSchool = $request->attributes->get("currentSchool");
        $deactivateHall = $this->hallService->deactivateHall($currentSchool, $hallId, $authAdmin);
        return ApiResponseService::success("Hall Deactivated Successfully", $deactivateHall, null, 200);
    }

    public function getHallDetails(Request $request, string $hallId)
    {
        $currentSchool = $request->attributes->get("currentSchool");
        $hall = $this->hallService->getHallDetails($currentSchool, $hallId);
        return ApiResponseService::success("Hall Details Fetched Successfully", $hall, null, 200);
    }

    public function importHall(ImportHallRequest $request)
    {
        $authUser = $this->resolveUser();
        $currentSchool = $request->attributes->get('currentSchool');
        $category = SystemJobCategory::where('name', 'Department')->firstOrFail();

        $filePath = $request->file('file')->store(
            "imports/hall/{$currentSchool->id}",
            'r2'
        );

        $payload = $request->validated();
        $payload['file_path'] = $filePath;
        unset($payload['file']);

        $systemJob = SystemJob::create([
            'type'              => 'department_import',
            'context_type'      => Hall::class,
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

       HallImportJob::dispatch(
            $authUser->id,
            $currentSchool->id,
            $category->id,
            $systemJob->id,
            $payload
        );

        return ApiResponseService::success(
            'Hall Importation Process Initiated Successfully',
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
