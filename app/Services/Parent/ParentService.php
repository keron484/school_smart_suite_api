<?php

namespace App\Services\Parent;

use App\Models\Parents;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\ApiResponseService;
use App\Events\Actions\AdminActionEvent;

class ParentService
{
    public function createParent($parentData, $currentSchool, $authAdmin)
    {
        $parent = Parents::create([
            'school_branch_id' => $currentSchool->id,
            'name' => $parentData['name'],
            'address' => $parentData['address'],
            "phone" => $parentData['phone'],
            "preferred_language" => $parentData['preferred_language'],
            "preferred_contact_method" => $parentData["preferred_contact_method"]
        ]);
        AdminActionEvent::dispatch(
            [
                "permissions" => ["schoolAdmin.parent.create"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" => $currentSchool->id,
                "feature" => "parentManagement",
                "action" => "parent.created",
                "authAdmin" => $authAdmin,
                "data" => $parent,
                "message" => "Parent Created",
            ]
        );
        return $parent;
    }
    public function getAllParents(object $currentSchool)
    {
        $parents = Parents::Where('school_branch_id', $currentSchool->id)->with('student')->get();
        return $parents;
    }

    public function deleteParent($parentId, $currentSchool, $authAdmin)
    {
        $parentExist = Parents::where("school_branch_id", $currentSchool->id)->find($parentId);
        if (!$parentExist) {
            return ApiResponseService::error("Parent Not Found", null, 404);
        }
        $parentExist->delete();
        AdminActionEvent::dispatch(
            [
                "permissions" => ["schoolAdmin.parent.delete"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" => $currentSchool->id,
                "feature" => "parentManagement",
                "action" => "parent.deleted",
                "authAdmin" => $authAdmin,
                "data" => $parentExist,
                "message" => "Parent Created",
            ]
        );
        return $parentExist;
    }

    public function bulkDeleteParent($parentIds, $currentSchool, $authAdmin)
    {
        $result = [];
        try {
            DB::beginTransaction();
            foreach ($parentIds as $parentId) {
                $parent = Parents::where("school_branch_id", $currentSchool->id)->findOrFail($parentId);
                $parent->delete();
                $result[] = [
                    $parent
                ];
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.parent.delete"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "parentManagement",
                    "action" => "parent.deleted",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Parent Deleted",
                ]
            );
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateParent(array $data, $parentId, $currentSchool, $authAdmin)
    {
        $parentExist = Parents::where("school_branch_id", $currentSchool->id)->find($parentId);
        if (!$parentExist) {
            return ApiResponseService::error("Parent Not Found", null, 404);
        }
        $filterData = array_filter($data);
        $parentExist->update($filterData);
        AdminActionEvent::dispatch(
            [
                "permissions" => ["schoolAdmin.parent.update"],
                "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                "schoolBranch" => $currentSchool->id,
                "feature" => "parentManagement",
                "action" => "parent.updated",
                "authAdmin" => $authAdmin,
                "data" => $parentExist,
                "message" => "Parent Updated",
            ]
        );
        return $parentExist;
    }

    public function bulkUpdateParent(array $updateDataArray, $currentSchool, $authAdmin)
    {
        $result = [];
        try {
            DB::beginTransaction();
            foreach ($updateDataArray as $updateData) {
                $parent = Parents::findOrFail($updateData['parent_id']);
                $filterData = array_filter($updateData);
                $parent->update($filterData);
                $result[] = $parent;
            }
            DB::commit();
            AdminActionEvent::dispatch(
                [
                    "permissions" => ["schoolAdmin.parent.update"],
                    "roles" => ["schoolSuperAdmin", "schoolAdmin"],
                    "schoolBranch" => $currentSchool->id,
                    "feature" => "parentManagement",
                    "action" => "parent.updated",
                    "authAdmin" => $authAdmin,
                    "data" => $result,
                    "message" => "Parent Updated",
                ]
            );
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getParentDetails($parentId, $currentSchool)
    {
        $parentDetails = Parents::where("school_branch_id", $currentSchool->id)
            ->where("id", $parentId)
            ->with(['student.specialty', 'student.level'])
            ->get();
        if (!$parentDetails) {
            return ApiResponseService::error("Parent not found", null, 404);
        }
        return $parentDetails;
    }
}
