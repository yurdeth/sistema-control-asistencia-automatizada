<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartamentosController;
use App\Http\Controllers\MateriasController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use Illuminate\Support\Facades\Route;

// routes/api.php

Route::post('/login', [AuthController::class, "login"])->name('login');

Route::middleware(['auth:api', 'throttle:1200,1', NoBrowserCacheMiddleware::class])->group(function () {
    Route::post('/logout', [AuthController::class, "logout"])->name('logout');

    //************************************ MANAGE ROLES ************************************//
    Route::get('/roles/get/all', [RolesController::class, 'index'])->name('roles.index');
    Route::get('/roles/get/{id}', [RolesController::class, 'show'])->name('roles.show');
    Route::post('/roles/new', [RolesController::class, 'store'])->name('roles.store');
    Route::delete('/roles/delete/{id}', [RolesController::class, 'destroy'])->name('roles.delete');
    Route::patch('/roles/edit/{id}', [RolesController::class, 'edit'])->name('roles.edit');

    //************************************ MANAGE USERS ************************************//
    Route::get('/users/get/all', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/get/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/new', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])->name('users.delete');
    Route::patch('/users/edit/{id}', [UserController::class, 'edit'])->name('users.edit');
    Route::get('/users/get/role/{id}', [UserController::class, 'getByRole'])->name('users.getByRole');
    Route::get('/users/get/department/{id}', [UserController::class, 'getByDepartment'])->name('users.getByDepartment');
    Route::get('/users/get/status/{estado}', [UserController::class, 'getByStatus'])->name('users.getByRoleAndDepartment');
    Route::get('/users/get/subject/{id}', [UserController::class, 'getBySubject'])->name('users.getBySubject');
    Route::get('/users/get/academics/all', [UserController::class, 'getAdministradoresAcademicosOnly'])->name('users.getAcademics');
    Route::get('/users/get/department-managers/all', [UserController::class, 'getDepartmentManagersOnly'])->name('users.getDepManagers');
    Route::get('/users/get/career-managers/all', [UserController::class, 'getCareerManagersOnly'])->name('users.getCareerManagers');
    Route::get('/users/get/professors/all', [UserController::class, 'getProfessorsOnly'])->name('users.getProfessors');
    Route::get('/users/get/profile', [UserController::class, 'getMyProfile'])->name('users.getMyProfile');

    //************************************ MANAGE DEPARTMENTS ************************************//
    Route::get('/departaments/get/all', [DepartamentosController::class, 'index'])->name('departamentos.index');
    Route::get('/departaments/get/{id}', [DepartamentosController::class, 'show'])->name('departamentos.show');
    Route::post('/departaments/new', [DepartamentosController::class, 'store'])->name('departamentos.store');
    Route::delete('/departaments/delete/{id}', [DepartamentosController::class, 'destroy'])->name('departamentos.delete');
    Route::patch('/departaments/edit/{id}', [DepartamentosController::class, 'edit'])->name('departamentos.edit');
    Route::get('/departaments/get/name/{nombre}', [DepartamentosController::class, 'getByDepartmentName'])->name('departamentos.getByName');
    Route::get('/departaments/get/status/{estado}', [DepartamentosController::class, 'getByStatus'])->name('departamentos.getByStatus');
    Route::get('/departaments/get/manager/name', [DepartamentosController::class, 'getManagers'])->name('departamentos.getManagers');
    Route::get('/departaments/get/manager/{id}', [DepartamentosController::class, 'getByManager'])->name('departamentos.getByManager');

    //************************************ MANAGE SUBJECTS ************************************//
    Route::get('/subjects/get/all', [MateriasController::class, 'index'])->name('materias.index');
    Route::get('/subjects/get/{id}', [MateriasController::class, 'show'])->name('materias.show');
    Route::post('/subjects/new', [MateriasController::class, 'store'])->name('materias.store');
    Route::patch('/subjects/edit/{id}', [MateriasController::class, 'edit'])->name('materias.edit');
    Route::delete('/subjects/delete/{id}', [MateriasController::class, 'destroy'])->name('materias.delete');
    Route::get('/subjects/get/department/{id}', [MateriasController::class, 'getMateriasByDepartment'])->name('materias.getByDepartment');
    Route::get('/subjects/get/status/{estado}', [MateriasController::class, 'getMateriasByStatus'])->name('materias.getByStatus');
//    Route::get('/subjects/get/user/{id}', [MateriasController::class, 'getMateriasByUserId'])->name('materias.getByUserId');
//    Route::get('/subjects/my-subjects/get', [MateriasController::class, 'getMySubjects'])->name('materias.getMySubjects');
});
