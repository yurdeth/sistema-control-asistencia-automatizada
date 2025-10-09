<?php

use App\Http\Controllers\AulaRecursosController;
use App\Http\Controllers\AulasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CiclosAcademicosController;
use App\Http\Controllers\DepartamentosController;
use App\Http\Controllers\GruposController;
use App\Http\Controllers\HorariosController;
use App\Http\Controllers\MateriasController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use Illuminate\Support\Facades\Route;

// routes/api.php

Route::get('/login', [AuthController::class, "checkAuth"])->name('login.get');
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
    Route::get('/users/get/profile/me', [UserController::class, 'getMyProfile'])->name('users.getMyProfile');

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

 //************************************ MANAGE GROUPS ************************************//
    Route::get('/groups/get/all', [GruposController::class, 'index'])->name('grupos.index');
    Route::get('/groups/get/{id}', [GruposController::class, 'show'])->name('grupos.show');
    Route::post('/groups/new', [GruposController::class, 'store'])->name('grupos.store');
    Route::patch('/groups/edit/{id}', [GruposController::class, 'edit'])->name('grupos.edit');
    Route::delete('/groups/delete/{id}', [GruposController::class, 'destroy'])->name('grupos.delete');
    Route::get('/groups/get/subject/{id}', [GruposController::class, 'getGroupsBySubject'])->name('grupos.getBySubject');
    Route::get('/groups/get/cycle/{id}', [GruposController::class, 'getGroupsByCycle'])->name('grupos.getByCycle');
    Route::get('/groups/get/professor/{id}', [GruposController::class, 'getGroupsByProfessor'])->name('grupos.getByProfessor');
    Route::get('/groups/get/status/{estado}', [GruposController::class, 'getGroupsByStatus'])->name('grupos.getByStatus');
    Route::get('/groups/get/available/all', [GruposController::class, 'getAvailableGroups'])->name('grupos.getAvailable');
    Route::get('/groups/get/number/{numero_grupo}', [GruposController::class, 'getGroupsByNumber'])->name('grupos.getByNumber');

    //************************************ MANAGE CLASSROOMS ************************************//
    Route::get('/classrooms/get/all', [AulasController::class, 'index'])->name('aulas.index');
    Route::get('/classrooms/get/{id}', [AulasController::class, 'show'])->name('aulas.show');
    Route::post('/classrooms/new', [AulasController::class, 'store'])->name('aulas.store');
    Route::patch('/classrooms/edit/{id}', [AulasController::class, 'edit'])->name('aulas.edit');
    Route::delete('/classrooms/delete/{id}', [AulasController::class, 'destroy'])->name('aulas.delete');
    Route::get('/classrooms/get/code/{codigo}', [AulasController::class, 'getClassroomByCode'])->name('aulas.getByCode');
    Route::get('/classrooms/get/status/{estado}', [AulasController::class, 'getClassroomsByStatus'])->name('aulas.getByStatus');
    Route::get('/classrooms/get/capacity/min/{capacidad}', [AulasController::class, 'getClassroomsByMinCapacity'])->name('aulas.getByMinCapacity');
    Route::get('/classrooms/get/available/all', [AulasController::class, 'getAvailableClassrooms'])->name('aulas.getAvailable');
    Route::get('/classrooms/get/location/{ubicacion}', [AulasController::class, 'getClassroomsByLocation'])->name('aulas.getByLocation');
    Route::get('/classrooms/get/qr/{qr_code}', [AulasController::class, 'getClassroomByQrCode'])->name('aulas.getByQrCode');
    Route::patch('/classrooms/change-status/{id}', [AulasController::class, 'changeClassroomStatus'])->name('aulas.changeStatus');
    Route::get('/classrooms/get/statistics/{id}', [AulasController::class, 'getClassroomStatistics'])->name('aulas.getStatistics');
    Route::get('/classrooms/get/suggestions/all', [AulasController::class, 'getClassroomSuggestions'])->name('aulas.getSuggestions');

    //************************************ MANAGE SCHEDULES ************************************//
    Route::get('/schedules/get/all', [HorariosController::class, 'index'])->name('horarios.index');
    Route::get('/schedules/get/{id}', [HorariosController::class, 'show'])->name('horarios.show');
    Route::post('/schedules/new', [HorariosController::class, 'store'])->name('horarios.store');
    Route::patch('/schedules/edit/{id}', [HorariosController::class, 'edit'])->name('horarios.edit');
    Route::delete('/schedules/delete/{id}', [HorariosController::class, 'destroy'])->name('horarios.delete');
    Route::get('/schedules/get/group/{id}', [HorariosController::class, 'getSchedulesByGroup'])->name('horarios.getByGroup');
    Route::get('/schedules/get/classroom/{id}', [HorariosController::class, 'getSchedulesByClassroom'])->name('horarios.getByClassroom');
    Route::get('/schedules/get/day/{dia_semana}', [HorariosController::class, 'getSchedulesByDay'])->name('horarios.getByDay');
    Route::get('/schedules/get/conflicts/{id}', [HorariosController::class, 'getScheduleConflicts'])->name('horarios.getConflicts');
    Route::get('/schedules/get/availability/classroom/{id}', [HorariosController::class, 'getClassroomAvailability'])->name('horarios.getClassroomAvailability');
    Route::get('/schedules/get/range/all', [HorariosController::class, 'getSchedulesByRange'])->name('horarios.getByRange');

    //************************************ MANAGE CLASSROOM RESOURCES ************************************//
    Route::get('/classroom-resources/get/all', [AulaRecursosController::class, 'index'])->name('aulaRecursos.index');
    Route::get('/classroom-resources/get/{id}', [AulaRecursosController::class, 'show'])->name('aulaRecursos.show');
    Route::post('/classroom-resources/new', [AulaRecursosController::class, 'store'])->name('aulaRecursos.store');
    Route::patch('/classroom-resources/edit/{id}', [AulaRecursosController::class, 'edit'])->name('aulaRecursos.edit');
    Route::delete('/classroom-resources/delete/{id}', [AulaRecursosController::class, 'destroy'])->name('aulaRecursos.delete');
    Route::get('/classroom-resources/get/classroom/{id}', [AulaRecursosController::class, 'getResourcesByClassroom'])->name('aulaRecursos.getByClassroom');
    Route::get('/classroom-resources/get/resource/{id}', [AulaRecursosController::class, 'getClassroomsByResource'])->name('aulaRecursos.getByResource');
    Route::get('/classroom-resources/get/status/{estado}', [AulaRecursosController::class, 'getResourcesByStatus'])->name('aulaRecursos.getByStatus');
    Route::get('/classroom-resources/get/classroom/{id}/available', [AulaRecursosController::class, 'getAvailableResourcesByClassroom'])->name('aulaRecursos.getAvailableByClassroom');
    Route::get('/classroom-resources/get/search/all', [AulaRecursosController::class, 'searchClassroomsByResources'])->name('aulaRecursos.search');
    Route::patch('/classroom-resources/change-status/{id}', [AulaRecursosController::class, 'changeResourceStatus'])->name('aulaRecursos.changeStatus');
    Route::get('/classroom-resources/get/inventory/all', [AulaRecursosController::class, 'getInventory'])->name('aulaRecursos.getInventory');

    //************************************ MANAGE ACADEMIC TERM ************************************//
    Route::get('/academic-terms/get/all', [CiclosAcademicosController::class, 'index'])->name('academicTerms.index');
    Route::get('/academic-terms/get/{id}', [CiclosAcademicosController::class, 'show'])->name('academicTerms.show');
    Route::post('/academic-terms/new', [CiclosAcademicosController::class, 'store'])->name('academicTerms.store');
    Route::delete('/academic-terms/delete/{id}', [CiclosAcademicosController::class, 'destroy'])->name('academicTerms.delete');
    Route::patch('/academic-terms/edit/{id}', [CiclosAcademicosController::class, 'edit'])->name('academicTerms.edit');
    Route::get('/academic-terms/get/status/{estado}', [CiclosAcademicosController::class, 'getByStatus'])->name('academicTerms.getByStatus');
    Route::get('/academic-terms/get/current', [CiclosAcademicosController::class, 'getCurrentTerm'])->name('academicTerms.getCurrent');
});
