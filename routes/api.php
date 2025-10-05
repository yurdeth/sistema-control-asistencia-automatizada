<?php

use App\Http\Controllers\AuthController;
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
    Route::patch('/users/edit/{id}', [UserController::class, 'update'])->name('users.edit');
    Route::get('/users/get/role/{id}', [UserController::class, 'getByRole'])->name('users.getByRole');
    Route::get('/users/get/department/{id}', [UserController::class, 'getByDepartment'])->name('users.getByDepartment');
    Route::get('/users/get/status/{estado}', [UserController::class, 'getByStatus'])->name('users.getByRoleAndDepartment');
    Route::get('/users/get/subject/{id}', [UserController::class, 'getBySubject'])->name('users.getBySubject');
});
