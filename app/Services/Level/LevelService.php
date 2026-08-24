<?php

namespace App\Services\Level;

use App\Models\Educationlevels;
use App\Exceptions\AppException;
use Throwable;

class LevelService
{
    public function deleteEducationLevel(string $levelId)
    {
        $levelName = 'Unknown Level';

        try {
            $educationLevel = Educationlevels::find($levelId);

            if (!$educationLevel) {
                throw new AppException(
                    "Education Level ID '{$levelId}' not found for deletion.",
                    404,
                    "Education Level Not Found 🗑️",
                    "The education level you are trying to delete could not be found. It may have already been deleted.",
                    null
                );
            }

            $levelName = $educationLevel->name;

            $educationLevel->delete();

            return $educationLevel;
        } catch (AppException $e) {
            throw $e;
        } catch (Throwable $e) {
            $message = "Failed to delete education level '{$levelName}' (ID: {$levelId}). Error: " . $e->getMessage();

            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                throw new AppException(
                    $message,
                    409,
                    "Deletion Blocked 🔒",
                    "Cannot delete the education level '{$levelName}' because it is linked to active records (e.g., students, classes, or programs). Please remove all associations first.",
                    null
                );
            }

            throw new AppException(
                $message,
                500,
                "Deletion Failed 🛑",
                "A system error occurred during deletion. Please try again or contact support.",
                null
            );
        }
    }

    public function getEducationLevels()
    {
        try {
            $educationLevels = Educationlevels::whereHas("levelType", fn($query) => $query->where("program_name", "level_start_200"))->get();

            if ($educationLevels->isEmpty()) {
                throw new AppException(
                    "No education levels were found in the system.",
                    404,
                    "No Education Levels Found 📚",
                    "There are currently no education levels defined in the system. Please create a new level to get started.",
                    null
                );
            }

            return $educationLevels;
        } catch (AppException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new AppException(
                "Failed to retrieve education levels. Error: " . $e->getMessage(),
                500,
                "Data Retrieval Failed 🛑",
                "A system error occurred while trying to load the education levels. Please try again or contact support.",
                null
            );
        }
    }

    public function createEducationLevel(array $data)
    {
        $levelName = $data["name"];
        $levelValue = $data["level"];

        if (Educationlevels::where('name', $levelName)->exists()) {
            throw new AppException(
                "An education level with the name '{$levelName}' already exists.",
                409,
                "Duplicate Level Name 📛",
                "Please choose a unique name for the education level.",
                null
            );
        }

        if (Educationlevels::where('level', $levelValue)->exists()) {
            throw new AppException(
                "An education level with the value '{$levelValue}' already exists.",
                409,
                "Duplicate Level Value",
                "The level value you entered is already in use. Please ensure each level has a unique numeric value.",
                null
            );
        }

        try {
            $educationLevel = new Educationlevels();
            $educationLevel->name = $levelName;
            $educationLevel->level = $levelValue;
            $educationLevel->save();

            return $educationLevel;
        } catch (Throwable $e) {
            throw new AppException(
                "Failed to create education level '{$levelName}'. Error: " . $e->getMessage(),
                500,
                "Level Creation Failed",
                "A system error occurred while trying to save the new education level. Please try again or contact support.",
                null
            );
        }
    }

    public function updateEducationLevel(array $data, string $levelId)
    {
        try {
            $educationLevel = Educationlevels::find($levelId);

            if (!$educationLevel) {
                throw new AppException(
                    "Education level ID '{$levelId}' not found.",
                    404,
                    "Education Level Not Found 🔎",
                    "The education level record you are trying to update could not be found.",
                    null
                );
            }

            $filteredData = array_filter($data);

            if (isset($filteredData['name'])) {
                $newName = $filteredData['name'];
                if (Educationlevels::where('name', $newName)->where('id', '!=', $levelId)->exists()) {
                    throw new AppException(
                        "An education level with the name '{$newName}' already exists.",
                        409,
                        "Duplicate Level Name 📛",
                        "The name you entered is already in use by another level.",
                        null
                    );
                }
            }

            if (isset($filteredData['level'])) {
                $newLevelValue = $filteredData['level'];
                if (Educationlevels::where('level', $newLevelValue)->where('id', '!=', $levelId)->exists()) {
                    throw new AppException(
                        "An education level with the value '{$newLevelValue}' already exists.",
                        409,
                        "Duplicate Level Value 🔢",
                        "The level value you entered is already in use by another level.",
                        null
                    );
                }
            }

            $educationLevel->update($filteredData);
            return $educationLevel;
        } catch (AppException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new AppException(
                "Failed to update education level ID '{$levelId}'. Error: " . $e->getMessage(),
                500,
                "Level Update Failed 🛑",
                "A system error occurred while trying to save the changes to the education level. Please try again or contact support.",
                null
            );
        }
    }
}
