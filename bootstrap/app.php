<?php

use App\Exceptions\AppException;
use App\Exceptions\AuthException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\ApiResponseService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::prefix('api/v1/auth/student')
                ->group(base_path('routes/Auth/Student.php'));

            Route::prefix('api/v1/auth/school-admin')
                ->group(base_path('routes/Auth/SchoolAdmin.php'));

            Route::prefix('api/v1/school-admin')
                ->group(base_path('routes/Notification/SchoolAdminNotification.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/student')
                ->group(base_path('routes/Notification/StudentNotification.php'));

            Route::prefix('api/v1/auth/teacher')
                ->group(base_path('routes/Auth/Teacher.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class,])->prefix('api/v1/teacher')
                ->group(base_path('routes/Teacher/Teacher.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/pdf')
                ->group(base_path('routes/PDF/Pdf.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/school-branch-setting')
                ->group(base_path('routes/Setting/SchoolBranchSetting.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/resit-candidate')
                ->group(base_path('routes/Exam/ResitExamCandidate.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/school-admin')
                ->group(base_path('routes/SchoolAdmin/SchoolAdmin.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/student')
                ->group(base_path('routes/Student/Student.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/role')
                ->group(base_path('routes/RoleAndPermission/Role.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/hall')
                ->group(base_path("routes/Hall/Hall.php"));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/hall-type')
                ->group(base_path('routes/Hall/HallType.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/specialty-hall')
                ->group(base_path('routes/Hall/SpecialtyHall.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/permission')
                ->group(base_path('routes/RoleAndPermission/Permission.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/registration-fees')
                ->group(base_path("routes/Fee/RegistrationFee.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/resit-payment')
                ->group(base_path("routes/Resit/ResitPayment.php"));

            Route::prefix('api/v1/school-branch')
                ->group(base_path('routes/School/SchoolBranch.php'));

            Route::prefix('api/v1/school')
                ->group(base_path('routes/School/School.php'));

            Route::prefix('api/v1/country')
                ->group(base_path('routes/Country/Country.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class,])->prefix('api/v1/department')
                ->group(base_path('routes/Department/Department.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class,])->prefix('api/v1/specialty')
                ->group(base_path('routes/Specialty/Specialty.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class,])->prefix('api/v1/course')
                ->group(base_path('routes/Course/Course.php'));
            Route::middleware(['auth:sanctum'])->prefix('api/v1/course-type')
                ->group(base_path('routes/Course/CourseType.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/parent')
                ->group(base_path('routes/Parent/Parent.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/mark')
                ->group(base_path('routes/Mark/Mark.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/teacher-avialability')
                ->group(base_path('routes/Teacher/TeacherAvialability.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/level')
                ->group(base_path('routes/Level/Level.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/grade')
                ->group(base_path('routes/Grade/Grade.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/grade-scale')
                ->group(base_path('routes/Grade/GradeScale.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/grade-category')
                ->group(base_path('routes/Grade/GradeCategory.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam')
                ->group(base_path('routes/Exam/Exam.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam-timetable')
                ->group(base_path('routes/Exam/ExamTimetable.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/school-semester')
                ->group(base_path('routes/Semester/SchoolSemester.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/semester')
                ->group(base_path('routes/Semester/Semester.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/election-role')
                ->group(base_path("routes/Election/ElectionRole.php"));
            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/election-candidate')
                ->group(base_path('routes/Election/ElectionCandidate.php'));
            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/election-type')
                ->group(base_path('routes/Election/ElectionType.php'));
            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/election')
                ->group(base_path('routes/Election/Election.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/election-application')
                ->group(base_path("routes/Election/ElectionApplication.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/fee-schedule')
                ->group(base_path('routes/Fee/FeePaymentSchedule.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/fee-waiver')
                ->group(base_path("routes/Fee/FeeWaiver.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/additional-fees')
                ->group(base_path("routes/Fee/AdditionalFee.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/student-batch')
                ->group(base_path("routes/Student/StudentBatch.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/additional-fee-category')
                ->group(base_path("routes/Fee/AdditionalFeeCategory.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/tuition-fee')
                ->group(base_path('routes/Fee/FeePayment.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/letter-grade')
                ->group(base_path("routes/Grade/LetterGrade.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam-type')
                ->group(base_path("routes/Exam/ExamType.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/expenses-category')
                ->group(base_path("routes/SchoolExpenses/SchoolExpensesCategory.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/school-expenses')
                ->group(base_path("routes/SchoolExpenses/SchoolExpenses.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/resit-timetable')
                ->group(base_path("routes/Resit/ResitTimetable.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/student-resit')
                ->group(base_path("routes/Resit/StudentResit.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam-candidate')
                ->group(base_path("routes/Exam/ExamCandidates.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/activation-code-transaction')
                ->group(base_path("routes/ActivationCode/ActivationCodeTransaction.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam-results')
                ->group(base_path("routes/Exam/ExamResults.php"));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/student-parent-relationship')
                ->group(base_path("routes/Student/StudentParentRelationship.php"));

            Route::prefix('api/v1/app-admin')
                ->group(base_path("routes/AppAdmin/AppAdmin.php"));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/auth/app-admin')
                ->group(base_path("routes/Auth/AppAdmin.php"));

            Route::prefix('api/v1/subscription-payment')
                ->group(base_path("routes/Subscription/SubscriptionPayment.php"));

            Route::prefix('api/v1/school-subscription')
                ->group(base_path("routes/Subscription/SchoolSubscription.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/school-event')
                ->group(base_path("routes/Event/SchoolEvent.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/student-promotion')
                ->group(base_path("routes/Student/StudentPromotion.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/teacher-specialty')
                ->group(base_path("routes/Teacher/TeacherSpecialty.php"));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/academic-stats')
                ->group(base_path('routes/Stats/AcademicStats.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/financial-stats')
                ->group(base_path('routes/Stats/FinancialStats.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/operational-stats')
                ->group(base_path('routes/Stats/OperationalStats.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/resit-exam')
                ->group(base_path("routes/Exam/ResitExam.php"));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/announcement-label')
                ->group(base_path('routes/Annnouncement/AnnouncementLabel.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/announcement-tag')
                ->group(base_path('routes/Annnouncement/AnnouncementTag.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/announcement-category')
                ->group(base_path('routes/Annnouncement/AnnouncementCategory.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/announcement')
                ->group(base_path('routes/Annnouncement/Announcement.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/event-category')
                ->group(base_path('routes/Event/SchoolEventCategory.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/event-tag')
                ->group(base_path('routes/Event/SchoolEventTag.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/teacher-course')
                ->group(base_path('routes/Course/TeacherCoursePreference.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/fee-installment')
                ->group(base_path('routes/FeeInstallment/FeeInstallment.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/feature')
                ->group(base_path('routes/SubscriptionPlan/Feature.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/feature-plan')
                ->group(base_path('routes/SubscriptionPlan/FeaturePlan.php'));

            Route::prefix('api/v1/plan')
                ->group(base_path('routes/SubscriptionPlan/Plan.php'));

            Route::prefix('api/v1/payment-method-category')
                ->group(base_path('routes/PaymentMethod/PaymentMethodCategory.php'));

            Route::prefix('api/v1/payment-method')
                ->group(base_path('routes/PaymentMethod/PaymentMethod.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/activation-code')
                ->group(base_path('routes/ActivationCode/ActivationCode.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/activation-code-type')
                ->group(base_path('routes/ActivationCode/ActivationCodeType.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/student-source')
                ->group(base_path('routes/Student/StudentSource.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/gender')
                ->group(base_path('routes/Gender/Gender.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/school-academic-year')
                ->group(base_path('routes/AcademicYear/SchoolAcademicYear.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/system-academic-year')
                ->group(base_path('routes/AcademicYear/SystemAcademicYear.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/semester-timetable')
                ->group(base_path('routes/SemesterTimetable/SemesterTimetable.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/semester-timetable/version')
                ->group(base_path('routes/SemesterTimetable/SemesterTimetableVersion.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/semester-timetable/constraints')
                ->group(base_path('routes/SemTimetableConstraint/Constraint.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/semester-timetable/constraints-category')
                ->group(base_path('routes/SemTimetableConstraint/ConstraintCategory.php'));

            Route::middleware(['auth:sanctum'])->prefix('api/v1/semester-timetable/constraints-type')
                ->group(base_path('routes/SemTimetableConstraint/ConstraintType.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class,])->prefix('api/v1/joint-course')
                ->group(base_path('routes/JointCourse/JointCourse.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class,])->prefix('api/v1/joint-course-slot')
                ->group(base_path('routes/JointCourse/JointCourseSlot.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/semester-timetable-interpreter')
                ->group(base_path('routes/SemesterTimetable/SemesterTimetableInterpreter.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/semester-timetable-parser')
                ->group(base_path('routes/TimetableParser/SemesterTimetableParser.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/invigilator')
                ->group(base_path('routes/Invigilator/Invigilator.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam-invigilator')
                ->group(base_path('routes/Invigilator/ExamInvigilator.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/period-duration-type')
                ->group(base_path('routes/PeriodDuration/PeriodDurationType.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])
                ->prefix('api/v1/period-duration')
                ->group(base_path('routes/PeriodDuration/PeriodDuration.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/exam-timetable/version')
                ->group(base_path('routes/Exam/ExamTimetableVersion.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])
                ->prefix('api/v1/semester-timetable-helpers')
                ->group(base_path('routes/SemesterTimetable/SemesterTimetableHelper.php'));

            Route::middleware(['auth:sanctum', IdentifyTenant::class])->prefix('api/v1/system-jobs')
                ->group(base_path('routes/Job/Job.php'));

            Route::middleware(['auth:sanctum'])
                ->prefix('api/v1/qualification')
                ->group(base_path('routes/Qualification/Qualification.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthException $e, Request $request) {
            if ($request->expectsJson()) {
                $structuredError = [
                    'title' => $e->getTitle(),
                    'description' => $e->getDescription(),
                    'path' => null,
                ];
                return ApiResponseService::error($e->getMessage(), null, $e->getCode(), $structuredError);
            }
        });
        $exceptions->render(function (AppException $e, Request $request) {
            if ($request->expectsJson()) {
                $structuredError = [
                    'title' => $e->getTitle(),
                    'description' => $e->getDescription(),
                    'path' => $e->getPath(),
                ];
                return ApiResponseService::error($e->getMessage(), null, $e->getCode(), $structuredError);
            }
        });
    })->create();
