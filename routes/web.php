<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentOrgController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeAssignmentController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\EducationLevelController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeRelativeController;
use App\Http\Controllers\EmployeeExperienceController;
use App\Http\Controllers\EmployeeSkillController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractAppendixController;
use App\Http\Controllers\ContractGenerateController;
use App\Http\Controllers\ContractTemplateController;

/*** Login Routes ***/
Route::group(['middleware' => 'guest'], function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    // Password Reset Routes
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

/*** Authenticated Routes ***/
Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});


Route::group(['middleware' => 'auth'], function () {
    Route::get('/', function () {
        return Inertia::render('Home');
    });

    // User Management Routes - Authorization handled by UserPolicy
    // Bulk delete route must be defined before resource routes
    Route::delete('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])->name('users.force-delete');
    Route::resource('users', UserController::class);

    // Role Management Routes - Authorization handled by RolePolicy
    Route::delete('roles/bulk-delete', [RoleController::class, 'bulkDelete'])->name('roles.bulk-delete');
    Route::resource('roles', RoleController::class);

    // Activity Log Routes - Authorization handled by ActivityPolicy
    Route::delete('activity-logs/clear', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');
    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show', 'destroy']);

    // Backup Routes - Authorization handled by BackupPolicy
    Route::get('backup', [\App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
    Route::get('backup/download', [\App\Http\Controllers\BackupController::class, 'backup'])->name('backup.download');
    Route::get('backup/configurations', [\App\Http\Controllers\BackupController::class, 'configurations'])->name('backup.configurations');
    Route::post('backup/configurations', [\App\Http\Controllers\BackupController::class, 'storeConfiguration'])->name('backup.configurations.store');
    Route::put('backup/configurations/{configuration}', [\App\Http\Controllers\BackupController::class, 'updateConfiguration'])->name('backup.configurations.update');
    Route::delete('backup/configurations/{configuration}', [\App\Http\Controllers\BackupController::class, 'deleteConfiguration'])->name('backup.configurations.delete');
    Route::patch('backup/configurations/{configuration}', [\App\Http\Controllers\BackupController::class, 'toggleConfiguration'])->name('backup.configurations.toggle');
    Route::post('backup/configurations/test-google-drive', [\App\Http\Controllers\BackupController::class, 'testGoogleDrive'])->name('backup.configurations.test-google-drive');
    Route::post('backup/configurations/{configuration}/run', [\App\Http\Controllers\BackupController::class, 'runConfiguration'])->name('backup.configurations.run');

    // Google Drive OAuth routes - Keep role middleware for security
    Route::group(['middleware' => 'role:Super Admin'], function () {
        Route::post('/auth/google-drive/connect', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'redirectToGoogle'])->name('google-drive.connect');
        Route::get('/auth/google-drive/callback', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'handleCallback'])->name('google-drive.callback');
        Route::post('/auth/google-drive/exchange-token', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'exchangeToken'])->name('google-drive.exchange-token');
        Route::get('/api/google-drive/status', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'getConnectionStatus'])->name('google-drive.status');
        Route::get('/api/google-drive/folders', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'getFolders'])->name('google-drive.folders');
        Route::post('/api/google-drive/create-folder', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'createBackupFolder'])->name('google-drive.create-folder');
        Route::post('/api/google-drive/select-folder', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'saveFolderSelection'])->name('google-drive.select-folder');
        Route::post('/api/google-drive/disconnect', [\App\Http\Controllers\GoogleDriveOAuthController::class, 'disconnect'])->name('google-drive.disconnect');
    });

    // Department Routes
    // IMPORTANT: Bulk delete MUST come before resource routes to avoid route conflict
    Route::delete('departments/bulk-delete', [DepartmentController::class, 'bulkDelete'])->name('departments.bulk-delete');

    // Department order management API routes
    Route::get('/departments/next-order/{parentId?}', [DepartmentController::class, 'getNextOrderIndexApi'])->name('departments.next-order');
    Route::post('/departments/reorder', [DepartmentController::class, 'updateOrderIndexes'])->name('departments.reorder');

    // Department resource routes
    Route::resource('departments', DepartmentController::class)->except(['show']);

    // Department Org Chart Routes
    Route::get('/departments/org', [DepartmentOrgController::class, 'index'])->name('departments.org');       // Trang Inertia
    Route::get('/departments/tree', [DepartmentOrgController::class, 'roots'])->name('departments.tree');     // JSON (web)
    Route::get('/departments/children', [DepartmentOrgController::class, 'children'])->name('departments.children'); // JSON (web)
    Route::get('/departments/{departmentId}/employees', [DepartmentOrgController::class, 'employees'])->name('departments.employees'); // JSON employees

    // Employee
    Route::resource('employees', EmployeeController::class);

    // Trang tổng profile (tabs)
    Route::get('employees/{employee}/profile', [EmployeeEducationController::class, 'profile'])
        ->name('employees.profile');

    // Educations
    Route::get('employees/{employee}/educations', [EmployeeEducationController::class, 'index'])->name('employees.educations.index');
    Route::post('employees/{employee}/educations', [EmployeeEducationController::class, 'store'])->name('employees.educations.store');
    Route::put('employees/{employee}/educations/{education}', [EmployeeEducationController::class, 'update'])->name('employees.educations.update');
    Route::delete('employees/{employee}/educations/{education}', [EmployeeEducationController::class, 'destroy'])->name('employees.educations.destroy');
    Route::post('employees/{employee}/educations/bulk-delete', [EmployeeEducationController::class, 'bulkDelete'])->name('employees.educations.bulk-delete');

    // Relatives
    Route::get('employees/{employee}/relatives', [EmployeeRelativeController::class, 'index'])->name('employees.relatives.index');
    Route::post('employees/{employee}/relatives', [EmployeeRelativeController::class, 'store'])->name('employees.relatives.store');
    Route::put('employees/{employee}/relatives/{relative}', [EmployeeRelativeController::class, 'update'])->name('employees.relatives.update');
    Route::delete('employees/{employee}/relatives/{relative}', [EmployeeRelativeController::class, 'destroy'])->name('employees.relatives.destroy');
    Route::post('employees/{employee}/relatives/bulk-delete', [EmployeeRelativeController::class, 'bulkDelete'])->name('employees.relatives.bulk-delete');

    // Experiences
    Route::get('employees/{employee}/experiences', [EmployeeExperienceController::class, 'index'])->name('employees.experiences.index');
    Route::post('employees/{employee}/experiences', [EmployeeExperienceController::class, 'store'])->name('employees.experiences.store');
    Route::put('employees/{employee}/experiences/{experience}', [EmployeeExperienceController::class, 'update'])->name('employees.experiences.update');
    Route::delete('employees/{employee}/experiences/{experience}', [EmployeeExperienceController::class, 'destroy'])->name('employees.experiences.destroy');
    Route::post('employees/{employee}/experiences/bulk-delete', [EmployeeExperienceController::class, 'bulkDelete'])->name('employees.experiences.bulk-delete');

    // Skills (gán kỹ năng cho NV)
    Route::get('employees/{employee}/skills', [EmployeeSkillController::class, 'index'])->name('employees.skills.index');
    Route::post('employees/{employee}/skills', [EmployeeSkillController::class, 'store'])->name('employees.skills.store');
    Route::put('employees/{employee}/skills/{employeeSkill}', [EmployeeSkillController::class, 'update'])->name('employees.skills.update');
    Route::delete('employees/{employee}/skills/{employeeSkill}', [EmployeeSkillController::class, 'destroy'])->name('employees.skills.destroy');
    Route::post('employees/{employee}/skills/bulk-delete', [EmployeeSkillController::class, 'bulkDelete'])->name('employees.skills.bulk-delete');

    // Danh mục kỹ năng (quản trị)
    Route::get('skills', [EmployeeSkillController::class, 'skillIndex'])->name('skills.index');
    Route::post('skills', [EmployeeSkillController::class, 'skillStore'])->name('skills.store');
    Route::put('skills/{skill}', [EmployeeSkillController::class, 'skillUpdate'])->name('skills.update');
    Route::delete('skills/{skill}', [EmployeeSkillController::class, 'skillDestroy'])->name('skills.destroy');

    // Employee Assignment
    Route::resource('employee-assignments', EmployeeAssignmentController::class)->except(['show']);

    // Contract routes
    Route::get('contracts', [ContractController::class,'index'])->name('contracts.index');
    Route::post('contracts', [ContractController::class,'store'])->name('contracts.store');
    Route::put('contracts/{contract}', [ContractController::class,'update'])->name('contracts.update');
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::delete('contracts/{contract}', [ContractController::class,'destroy'])->name('contracts.destroy');
    Route::post('contracts/bulk-delete', [ContractController::class,'bulkDelete'])->name('contracts.bulk-delete');

    Route::post('contracts/{contract}/approve', [ContractController::class,'approve'])->name('contracts.approve');
    Route::post('contracts/{contract}/reject', [ContractController::class,'reject'])->name('contracts.reject');
    Route::post('contracts/{contract}/generate', [ContractGenerateController::class, 'generate'])->name('contracts.generate');

    // Contract Template routes
    Route::get('/contract-templates', [ContractTemplateController::class, 'index'])->name('contract-templates.index');
    Route::post('/contract-templates', [ContractTemplateController::class, 'store'])->name('contract-templates.store');
    Route::put('/contract-templates/{template}', [ContractTemplateController::class, 'update'])->name('contract-templates.update');
    Route::delete('/contract-templates/{template}', [ContractTemplateController::class, 'destroy'])->name('contract-templates.destroy');
    Route::post('/contract-templates/bulk-delete', [ContractTemplateController::class, 'bulkDelete'])->name('contract-templates.bulk-delete');

    // Contract Appendix routes (nested under contracts)
    Route::prefix('contracts/{contract}')->group(function () {
        Route::get('appendixes', [ContractAppendixController::class,'index'])->name('contracts.appendixes.index');
        Route::post('appendixes', [ContractAppendixController::class,'store'])->name('contracts.appendixes.store');
        Route::put('appendixes/{appendix}', [ContractAppendixController::class,'update'])->name('contracts.appendixes.update');
        Route::delete('appendixes/{appendix}', [ContractAppendixController::class,'destroy'])->name('contracts.appendixes.destroy');
        Route::post('appendixes/bulk-delete', [ContractAppendixController::class,'bulkDelete'])->name('contracts.appendixes.bulk-delete');

        Route::post('appendixes/{appendix}/approve', [ContractAppendixController::class,'approve'])->name('contracts.appendixes.approve');
        Route::post('appendixes/{appendix}/reject', [ContractAppendixController::class,'reject'])->name('contracts.appendixes.reject');

        Route::post('appendixes/{appendix}/generate', [ContractAppendixController::class, 'generate'])->name('contracts.appendixes.generate');
    });

    // Position Routes
    Route::delete('positions/bulk-delete', [\App\Http\Controllers\PositionController::class, 'bulkDelete'])->name('positions.bulk-delete');
    Route::resource('positions', \App\Http\Controllers\PositionController::class);

    // Province Routes
    Route::delete('provinces/bulk-delete', [ProvinceController::class, 'bulkDelete'])->name('provinces.bulk-delete');
    Route::resource('provinces', ProvinceController::class);

    // Ward Routes
    Route::delete('wards/bulk-delete', [WardController::class, 'bulkDelete'])->name('wards.bulk-delete');
    Route::resource('wards', WardController::class);

    // Address (Province & Ward) Routes for dropdowns (keep for compatibility)
    Route::get('/api/provinces', [ProvinceController::class, 'index'])->name('api.provinces.index');
    Route::get('/api/provinces/{province}/wards', [ProvinceController::class, 'getWards'])->name('api.provinces.wards');

    // Education Levels
    Route::get('education-levels', [EducationLevelController::class, 'index'])->name('education-levels.index');
    Route::post('education-levels', [EducationLevelController::class, 'store'])->name('education-levels.store');
    Route::put('education-levels/{education_level}', [EducationLevelController::class, 'update'])->name('education-levels.update');
    Route::delete('education-levels/{education_level}', [EducationLevelController::class, 'destroy'])->name('education-levels.destroy');
    Route::post('education-levels/bulk-delete', [EducationLevelController::class, 'bulkDelete'])->name('education-levels.bulk-delete');

    // Schools
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::put('schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
    Route::delete('schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');
    Route::post('schools/bulk-delete', [SchoolController::class, 'bulkDelete'])->name('schools.bulk-delete');
});
