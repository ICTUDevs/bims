<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssistanceRecordController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\BeneficiaryGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrainingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::middleware(['permission:dashboard.access'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    });

    Route::middleware(['permission:reports.export'])->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/beneficiaries/pdf', [ReportController::class, 'beneficiariesPdf'])->name('reports.beneficiaries.pdf');
        Route::get('reports/beneficiaries/excel', [ReportController::class, 'beneficiariesExcel'])->name('reports.beneficiaries.excel');
        Route::get('reports/projects/pdf', [ReportController::class, 'projectsPdf'])->name('reports.projects.pdf');
        Route::get('reports/projects/excel', [ReportController::class, 'projectsExcel'])->name('reports.projects.excel');
        Route::get('reports/trainings/pdf', [ReportController::class, 'trainingsPdf'])->name('reports.trainings.pdf');
        Route::get('reports/trainings/excel', [ReportController::class, 'trainingsExcel'])->name('reports.trainings.excel');
        Route::get('reports/assistance/pdf', [ReportController::class, 'assistancePdf'])->name('reports.assistance.pdf');
        Route::get('reports/assistance/excel', [ReportController::class, 'assistanceExcel'])->name('reports.assistance.excel');
        Route::get('reports/groups/pdf', [ReportController::class, 'groupsPdf'])->name('reports.groups.pdf');
        Route::get('reports/groups/excel', [ReportController::class, 'groupsExcel'])->name('reports.groups.excel');
        Route::get('reports/audit-logs/pdf', [ReportController::class, 'auditLogsPdf'])->name('reports.audit-logs.pdf');
        Route::get('reports/audit-logs/excel', [ReportController::class, 'auditLogsExcel'])->name('reports.audit-logs.excel');
    });

    Route::middleware(['permission:beneficiaries.view|beneficiaries.manage'])->group(function () {
        Route::get('beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
        Route::get('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'show'])->name('beneficiaries.show');
        Route::get('beneficiaries/{beneficiary}/details', [BeneficiaryController::class, 'details'])->name('beneficiaries.details');
        Route::get('beneficiaries/{beneficiary}/family', [FamilyMemberController::class, 'index'])->name('beneficiaries.family.index');
        Route::get('beneficiaries-search', [FamilyMemberController::class, 'searchBeneficiaries'])->name('beneficiaries.search');
        Route::get('beneficiaries-similarity', [FamilyMemberController::class, 'checkSimilarity'])->name('beneficiaries.similarity');
        Route::get('beneficiaries-family-match', [FamilyMemberController::class, 'checkFamilyMemberMatch'])->name('beneficiaries.family-match');
    });

    Route::middleware(['permission:beneficiaries.manage'])->group(function () {
        Route::post('beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
        Route::match(['put', 'patch'], 'beneficiaries/{beneficiary}', [BeneficiaryController::class, 'update'])->name('beneficiaries.update');
        Route::delete('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');
        Route::post('beneficiaries/{beneficiary}/family', [FamilyMemberController::class, 'store'])->name('beneficiaries.family.store');
        Route::put('beneficiaries/{beneficiary}/family/{member}', [FamilyMemberController::class, 'update'])->name('beneficiaries.family.update');
        Route::delete('beneficiaries/{beneficiary}/family/{member}', [FamilyMemberController::class, 'destroy'])->name('beneficiaries.family.destroy');
    });

    Route::middleware(['permission:projects.view|projects.manage'])->group(function () {
        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/beneficiaries', [ProjectController::class, 'listBeneficiaries'])->name('projects.beneficiaries.index');
        Route::get('projects/{project}/available-beneficiaries', [ProjectController::class, 'availableBeneficiaries'])->name('projects.beneficiaries.available');
        Route::get('projects/{project}/groups', [ProjectController::class, 'listGroups'])->name('projects.groups.index');
        Route::get('projects/{project}/available-groups', [ProjectController::class, 'availableGroups'])->name('projects.groups.available');
    });

    Route::middleware(['permission:projects.manage'])->group(function () {
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::match(['put', 'patch'], 'projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{project}/beneficiaries', [ProjectController::class, 'enrollBeneficiary'])->name('projects.beneficiaries.store');
        Route::delete('projects/{project}/beneficiaries/{beneficiary}', [ProjectController::class, 'removeBeneficiary'])->name('projects.beneficiaries.destroy');
        Route::post('projects/{project}/groups', [ProjectController::class, 'enrollGroup'])->name('projects.groups.store');
        Route::delete('projects/{project}/groups/{group}', [ProjectController::class, 'removeGroup'])->name('projects.groups.destroy');
    });

    Route::middleware(['permission:trainings.view|trainings.manage'])->group(function () {
        Route::get('trainings', [TrainingController::class, 'index'])->name('trainings.index');
        Route::get('trainings/{training}', [TrainingController::class, 'show'])->name('trainings.show');
        Route::get('trainings/{training}/participants', [TrainingController::class, 'listParticipants'])->name('trainings.participants.index');
        Route::get('trainings/{training}/available-participants', [TrainingController::class, 'availableParticipants'])->name('trainings.participants.available');
    });

    Route::middleware(['permission:trainings.manage'])->group(function () {
        Route::post('trainings', [TrainingController::class, 'store'])->name('trainings.store');
        Route::match(['put', 'patch'], 'trainings/{training}', [TrainingController::class, 'update'])->name('trainings.update');
        Route::delete('trainings/{training}', [TrainingController::class, 'destroy'])->name('trainings.destroy');
        Route::post('trainings/{training}/participants', [TrainingController::class, 'addParticipant'])->name('trainings.participants.store');
        Route::delete('trainings/{training}/participants/{beneficiary}', [TrainingController::class, 'removeParticipant'])->name('trainings.participants.destroy');
    });

    Route::middleware(['permission:assistance.view|assistance.manage'])->group(function () {
        Route::get('assistance-records', [AssistanceRecordController::class, 'index'])->name('assistance-records.index');
        Route::get('assistance-records/{assistance_record}', [AssistanceRecordController::class, 'show'])->name('assistance-records.show');
    });

    Route::middleware(['permission:assistance.manage'])->group(function () {
        Route::post('assistance-records', [AssistanceRecordController::class, 'store'])->name('assistance-records.store');
        Route::match(['put', 'patch'], 'assistance-records/{assistance_record}', [AssistanceRecordController::class, 'update'])->name('assistance-records.update');
        Route::delete('assistance-records/{assistance_record}', [AssistanceRecordController::class, 'destroy'])->name('assistance-records.destroy');
    });

    Route::middleware(['permission:groups.view|groups.manage'])->group(function () {
        Route::get('beneficiary-groups', [BeneficiaryGroupController::class, 'index'])->name('beneficiary-groups.index');
        Route::get('beneficiary-groups/{beneficiary_group}/members', [BeneficiaryGroupController::class, 'listMembers'])->name('beneficiary-groups.members.index');
        Route::get('beneficiary-groups/{beneficiary_group}/available-members', [BeneficiaryGroupController::class, 'availableMembers'])->name('beneficiary-groups.members.available');
    });

    Route::middleware(['permission:groups.manage'])->group(function () {
        Route::post('beneficiary-groups', [BeneficiaryGroupController::class, 'store'])->name('beneficiary-groups.store');
        Route::match(['put', 'patch'], 'beneficiary-groups/{beneficiary_group}', [BeneficiaryGroupController::class, 'update'])->name('beneficiary-groups.update');
        Route::delete('beneficiary-groups/{beneficiary_group}', [BeneficiaryGroupController::class, 'destroy'])->name('beneficiary-groups.destroy');
        Route::post('beneficiary-groups/{beneficiary_group}/members', [BeneficiaryGroupController::class, 'addMember'])->name('beneficiary-groups.members.store');
        Route::delete('beneficiary-groups/{beneficiary_group}/members/{beneficiary}', [BeneficiaryGroupController::class, 'removeMember'])->name('beneficiary-groups.members.destroy');
    });

    Route::middleware(['permission:audit_logs.view'])->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });

    Route::middleware(['permission:users.manage'])->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware(['permission:roles.manage'])->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware(['permission:notifications.access'])->group(function () {
        Route::get('app-notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('app-notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::patch('app-notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::delete('app-notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
});
