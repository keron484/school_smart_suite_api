<?php

//use Illuminate\Support\Facades\Broadcast;

//Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
// return (int) $user->id === (int) $id;
//});

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('election.results.{schoolBranchId}.{electionId}', function ($user, $schoolBranchId, $electionId) {
    return (string) $user->school_branch_id === (string) $schoolBranchId;
}, ['guards' => ['student', 'teacher', 'schooladmin']]);

Broadcast::channel('App.Models.Teacher.{id}', function ($user, $id) {
    return $user->id == $id;
}, ['guards' => ['teacher']]);

Broadcast::channel('App.Models.Student.{id}', function ($user, $id) {
    return $user->id == $id;
}, ['guards' => ['student']]);

Broadcast::channel('App.Models.Schooladmin.{id}', function ($user, $id) {
    return $user->id == $id;
}, ['guards' => ['schooladmin']]);

BroadCast::channel('schoolBranch.{schoolBranchId}.schoolAdmin.{schoolAdminId}.actions', function ($user, $schoolBranchId) {
    return (string) $user->school_branch_id === (string) $schoolBranchId;
}, ['guards' => ['schooladmin']]);

BroadCast::channel('schoolBranch.{schoolBranchId}.student.{studentId}.actions', function ($user, $schoolBranchId) {
    return (string) $user->school_branch_id === (string) $schoolBranchId;
}, ['guards' => ['student']]);

Broadcast::channel('schoolBranch.{schoolBranchId}.schoolAdmin.{schoolAdminId}.semesterTimetable', function ($user, $schoolBranchId) {
    return (string) $user->school_branch_id === (string) $schoolBranchId;
}, ['guards' => ['schooladmin']]);

Broadcast::channel('schoolBranch.{schoolBranchId}.schoolAdmin.{schoolAdminId}.jobs', function ($user, $schoolBranchId) {
    return (string) $user->school_branch_id === (string) $schoolBranchId;
}, ['guards' => ['schooladmin']]);
