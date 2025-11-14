<?php

use App\Http\Controllers\AsistenciasEstudiantesController;
use App\Http\Controllers\AulaRecursosController;
use App\Http\Controllers\AulasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarrerasController;
use App\Http\Controllers\CiclosAcademicosController;
use App\Http\Controllers\ConfiguracionSistemaController;
use App\Http\Controllers\DepartamentosController;
use App\Http\Controllers\EscaneosQrController;
use App\Http\Controllers\EstadisticasAulasDiariasController;
use App\Http\Controllers\GruposController;
use App\Http\Controllers\HistorialAulasController;
use App\Http\Controllers\HorariosController;
use App\Http\Controllers\InscripcionesController;
use App\Http\Controllers\MantenimientosController;
use App\Http\Controllers\MateriasController;
use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\RecursosTipoController;
use App\Http\Controllers\ReportesProblemasAulasController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SesionesClaseController;
use App\Http\Controllers\SolicitudesInscripcionController;
use App\Http\Controllers\SystemLogsController;
use App\Http\Controllers\TiposNotificacionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use Illuminate\Support\Facades\Route;

// routes/api.php

Route::get('/login', function () {
    return response()->json([
        'message' => 'Sesión expirada o inválida. Por favor, inicie sesión de nuevo.',
        'success' => false
    ], 405);
})->name('login.get');

Route::post('/login', [AuthController::class, "login"])->name('login.post');
//Route::post('/login-web', [AuthController::class, "loginWeb"])->name('login.post.web');
Route::post('/login-as-guest', [AuthController::class, "loginAsGuest"])->name('login.guest');

// Password Reset - Web (envía link en email)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot.password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset.password');
Route::post('/validate-reset-token', [AuthController::class, 'validateResetToken'])->name('validate.reset.token');

// Password Reset - Mobile (envía código de 6 dígitos)
Route::post('/forgot-password-mobile', [AuthController::class, 'forgotPasswordMobile'])->name('forgot.password.mobile');
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode'])->name('verify.reset.code');

Route::middleware(['auth:api', 'throttle:1200,1', NoBrowserCacheMiddleware::class])->group(function () {
    Route::post('/logout', [AuthController::class, "logout"])->name('logout');
    Route::get('/verify-token', [AuthController::class, 'verifyToken']);
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change.password');

    //************************************ MANAGE ROLES ************************************//
    Route::get('/roles/get/all', [RolesController::class, 'index'])->name('roles.index');
    Route::get('/roles/get/{id}', [RolesController::class, 'show'])->name('roles.show');
    Route::post('/roles/new', [RolesController::class, 'store'])->name('roles.store');
    Route::delete('/roles/delete/{id}', [RolesController::class, 'destroy'])->name('roles.delete');
    Route::patch('/roles/edit/{id}', [RolesController::class, 'edit'])->name('roles.edit');
    Route::get('/roles/get/users/{id}', [RolesController::class, 'getUsersWithRolId'])->name('roles.getUserRoleName');

    //************************************ MANAGE USERS ************************************//
    Route::get('/users/get/all', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/get/{id}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users/new', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])->name('users.delete');
    Route::post('/users/deactivate/{id}', [UserController::class, 'disableAccount'])->name('users.disableAccount');
    Route::post('/users/activate/{id}', [UserController::class, 'enableAccount'])->name('users.enableAccount');
    Route::patch('/users/edit/{id}', [UserController::class, 'edit'])->name('users.edit');
    Route::get('/users/get/role/{id}', [UserController::class, 'getByRole'])->name('users.getByRole');
    Route::get('/users/get/department/{id}', [UserController::class, 'getByDepartment'])->name('users.getByDepartment');
    Route::post('/users/get/department/by-role/', [UserController::class, 'getByDepartmentByRole'])->name('users.getByDepartmentByRole');
    Route::post('/users/get/career/by-role/', [UserController::class, 'getByCareerByRole'])->name('users.getByCareerByRole');
    Route::post('/users/get/status', [UserController::class, 'getByStatus'])->name('users.getByStatus');
    Route::post('/users/get/status-role', [UserController::class, 'getByStatusByRole'])->name('users.getByStatusByRole');
    Route::get('/users/get/subject/{id}', [UserController::class, 'getBySubject'])->name('users.getBySubject');
    Route::get('/users/get/academics/all', [UserController::class, 'getAdministradoresAcademicosOnly'])->name('users.getAcademics');
    Route::get('/users/get/department-managers/all', [UserController::class, 'getDepartmentManagersOnly'])->name('users.getDepManagers');
    Route::get('/users/get/career-managers/all', [UserController::class, 'getCareerManagersOnly'])->name('users.getCareerManagers');
    Route::get('/users/get/professors/all', [UserController::class, 'getProfessorsOnly'])->name('users.getProfessors');
    Route::get('/users/get/students/all', [UserController::class, 'getStudentsOnly'])->name('users.getStudents');
    Route::get('/users/get/profile/me', [UserController::class, 'getMyProfile'])->name('users.getMyProfile');
    Route::post('/users/get/name', [UserController::class, 'getByName'])->name('users.getByName');

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
    Route::get('/subjects/get/by-career/{id}', [MateriasController::class, 'getMateriasByCareerId'])->name('materias.getByCareer');
    Route::get('/subjects/get/status/{estado}', [MateriasController::class, 'getMateriasByStatus'])->name('materias.getByStatus');
    Route::get('/subjects/get/user/{id}', [MateriasController::class, 'getSubjectsByUserId'])->name('materias.getByUser');
    Route::get('/subjects/get/my/all', [MateriasController::class, 'getMySubjects'])->name('materias.getMySubjects');

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
    Route::post('/groups/get/professor/', [GruposController::class, 'getGroupProfessor'])->name('grupos.getGroupProfessor');

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
    Route::post('/classrooms/suggestions/all', [AulasController::class, 'getClassroomSuggestions'])->name('aulas.getSuggestions');

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
    Route::post('/schedules/get/range/all', [HorariosController::class, 'getSchedulesByRange'])->name('horarios.getByRange');

    //************************************ MANAGE CLASSROOM RESOURCES ************************************//
    Route::get('/classroom-resources/get/all', [AulaRecursosController::class, 'index'])->name('aulaRecursos.index');
    Route::get('/classroom-resources/get/{id}', [AulaRecursosController::class, 'show'])->name('aulaRecursos.show');
    Route::post('/classroom-resources/new', [AulaRecursosController::class, 'store'])->name('aulaRecursos.store');
    Route::patch('/classroom-resources/edit/{id}', [AulaRecursosController::class, 'edit'])->name('aulaRecursos.edit');
    Route::delete('/classroom-resources/delete/{id}', [AulaRecursosController::class, 'destroy'])->name('aulaRecursos.delete');
    Route::get('/classroom-resources/get/classroom/{id}', [AulaRecursosController::class, 'getResourcesByClassroom'])->name('aulaRecursos.getByClassroom');
    Route::get('/classroom-resources/get/resource/{id}', [AulaRecursosController::class, 'getClassroomsByResource'])->name('aulaRecursos.getByResource');
    Route::post('/classroom-resources/get/status/all', [AulaRecursosController::class, 'getResourcesByStatus'])->name('aulaRecursos.getByStatus');
    Route::patch('/classroom-resources/change-status/{id}', [AulaRecursosController::class, 'changeResourceStatus'])->name('aulaRecursos.changeStatus');
    Route::get('/classroom-resources/get/inventory/all', [AulaRecursosController::class, 'getInventory'])->name('aulaRecursos.getInventory');

    //************************************ MANAGE ACADEMIC TERM ************************************//
    Route::get('/academic-terms/get/all', [CiclosAcademicosController::class, 'index'])->name('academicTerms.index');
    Route::get('/academic-terms/get/current', [CiclosAcademicosController::class, 'getCurrentTerm'])->name('academicTerms.getCurrent');
    Route::get('/academic-terms/get/status/{estado}', [CiclosAcademicosController::class, 'getByStatus'])->name('academicTerms.getByStatus');
    Route::get('/academic-terms/get/{id}', [CiclosAcademicosController::class, 'show'])->name('academicTerms.show');
    Route::post('/academic-terms/new', [CiclosAcademicosController::class, 'store'])->name('academicTerms.store');
    Route::delete('/academic-terms/delete/{id}', [CiclosAcademicosController::class, 'destroy'])->name('academicTerms.delete');
    Route::patch('/academic-terms/edit/{id}', [CiclosAcademicosController::class, 'edit'])->name('academicTerms.edit');

    //************************************ MANAGE RESOURCE TYPES ************************************//
    Route::get('/resource-types/get/all', [RecursosTipoController::class, 'index'])->name('resourceTypes.index');
    Route::get('/resource-types/get/{id}', [RecursosTipoController::class, 'show'])->name('resourceTypes.show');
    Route::post('/resource-types/new', [RecursosTipoController::class, 'store'])->name('resourceTypes.store');
    Route::patch('/resource-types/edit/{id}', [RecursosTipoController::class, 'edit'])->name('resourceTypes.edit');
    Route::delete('/resource-types/delete/{id}', [RecursosTipoController::class, 'destroy'])->name('resourceTypes.delete');

    //************************************ MANAGE ENROLLMENT REQUESTS ************************************//
    Route::get('/enrollment-requests/get/all', [SolicitudesInscripcionController::class, 'index'])->name('enrollmentRequests.index');
    Route::get('/enrollment-requests/get/{id}', [SolicitudesInscripcionController::class, 'show'])->name('enrollmentRequests.show');
    Route::post('/enrollment-requests/new', [SolicitudesInscripcionController::class, 'store'])->name('enrollmentRequests.store');
    Route::patch('/enrollment-requests/edit/{id}', [SolicitudesInscripcionController::class, 'edit'])->name('enrollmentRequests.edit');
    Route::delete('/enrollment-requests/delete/{id}', [SolicitudesInscripcionController::class, 'destroy'])->name('enrollmentRequests.delete');
    Route::get('/enrollment-requests/get/student/{id}', [SolicitudesInscripcionController::class, 'getByStudent'])->name('enrollmentRequests.getByStudent');
    Route::get('/enrollment-requests/get/group/{id}', [SolicitudesInscripcionController::class, 'getByGroup'])->name('enrollmentRequests.getByGroup');
    Route::get('/enrollment-requests/get/status/{estado}', [SolicitudesInscripcionController::class, 'getByStatus'])->name('enrollmentRequests.getByStatus');
    Route::get('/enrollment-requests/get/type/{tipo}', [SolicitudesInscripcionController::class, 'getByType'])->name('enrollmentRequests.getByType');
    Route::post('/enrollment-requests/accept/{id}', [SolicitudesInscripcionController::class, 'acceptRequest'])->name('enrollmentRequests.accept');
    Route::post('/enrollment-requests/reject/{id}', [SolicitudesInscripcionController::class, 'rejectRequest'])->name('enrollmentRequests.reject');
    Route::get('/enrollment-requests/get/pending/professor/{id}', [SolicitudesInscripcionController::class, 'getPendingByProfessor'])->name('enrollmentRequests.getPendingByProfessor');

    //************************************ MANAGE ENROLLMENTS ************************************//
    Route::get('/enrollments/get/all', [InscripcionesController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/get/{id}', [InscripcionesController::class, 'show'])->name('enrollments.show');
    Route::post('/enrollments/new', [InscripcionesController::class, 'store'])->name('enrollments.store');
    Route::patch('/enrollments/edit/{id}', [InscripcionesController::class, 'edit'])->name('enrollments.edit');
    Route::delete('/enrollments/delete/{id}', [InscripcionesController::class, 'destroy'])->name('enrollments.delete');
    Route::get('/enrollments/get/student/{id}', [InscripcionesController::class, 'getByStudent'])->name('enrollments.getByStudent');
    Route::get('/enrollments/get/group/{id}', [InscripcionesController::class, 'getByGroup'])->name('enrollments.getByGroup');
    Route::get('/enrollments/get/status/{estado}', [InscripcionesController::class, 'getByStatus'])->name('enrollments.getByStatus');
    Route::post('/enrollments/withdraw/{id}', [InscripcionesController::class, 'withdrawEnrollment'])->name('enrollments.withdraw');
    Route::get('/enrollments/get/student/{student_id}/active/all', [InscripcionesController::class, 'getActiveByStudent'])->name('enrollments.getActiveByStudent');

    //************************************ MANAGE CLASS SESSIONS ************************************//
    Route::get('/class-sessions/get/all', [SesionesClaseController::class, 'index'])->name('classSessions.index');
    Route::get('/class-sessions/get/{id}', [SesionesClaseController::class, 'show'])->name('classSessions.show');
    Route::post('/class-sessions/new', [SesionesClaseController::class, 'store'])->name('classSessions.store');
    Route::patch('/class-sessions/edit/{id}', [SesionesClaseController::class, 'edit'])->name('classSessions.edit');
    Route::delete('/class-sessions/delete/{id}', [SesionesClaseController::class, 'destroy'])->name('classSessions.delete');
    Route::get('/class-sessions/get/group/{id}', [SesionesClaseController::class, 'getByGroup'])->name('classSessions.getByGroup');
    Route::get('/class-sessions/get/schedule/{id}', [SesionesClaseController::class, 'getBySchedule'])->name('classSessions.getBySchedule');
    Route::get('/class-sessions/get/status/{estado}', [SesionesClaseController::class, 'getByStatus'])->name('classSessions.getByStatus');
    Route::post('/class-sessions/start', [SesionesClaseController::class, 'startSession'])->name('classSessions.start');
    Route::post('/class-sessions/finish/{id}', [SesionesClaseController::class, 'finishSession'])->name('classSessions.finish');
    Route::get('/class-sessions/get/professor/{id}/today', [SesionesClaseController::class, 'getTodayByProfessor'])->name('classSessions.getTodayByProfessor');
    Route::get('/class-sessions/get/date/{fecha}', [SesionesClaseController::class, 'getByDate'])->name('classSessions.getByDate');
    Route::patch('/class-sessions/change-status/{id}', [SesionesClaseController::class, 'changeStatus'])->name('classSessions.changeStatus');

    //************************************ MANAGE STUDENT ATTENDANCE ************************************//
    Route::get('/student-attendance/get/all', [AsistenciasEstudiantesController::class, 'index'])->name('studentAttendance.index');
    Route::get('/student-attendance/get/{id}', [AsistenciasEstudiantesController::class, 'show'])->name('studentAttendance.show');
    Route::post('/student-attendance/new', [AsistenciasEstudiantesController::class, 'store'])->name('studentAttendance.store');
    Route::patch('/student-attendance/edit/{id}', [AsistenciasEstudiantesController::class, 'edit'])->name('studentAttendance.edit');
    Route::delete('/student-attendance/delete/{id}', [AsistenciasEstudiantesController::class, 'destroy'])->name('studentAttendance.delete');
    Route::get('/student-attendance/get/session/{id}', [AsistenciasEstudiantesController::class, 'getBySession'])->name('studentAttendance.getBySession');
    Route::get('/student-attendance/get/student/{id}', [AsistenciasEstudiantesController::class, 'getByStudent'])->name('studentAttendance.getByStudent');
    Route::get('/student-attendance/get/status/{estado}', [AsistenciasEstudiantesController::class, 'getByStatus'])->name('studentAttendance.getByStatus');
    Route::post('/student-attendance/register', [AsistenciasEstudiantesController::class, 'registerAttendance'])->name('studentAttendance.register');
    Route::get('/student-attendance/get/student/{student_id}/group/{group_id}', [AsistenciasEstudiantesController::class, 'getAttendanceReport'])->name('studentAttendance.getReport');
    Route::get('/student-attendance/get/student/{student_id}/statistics', [AsistenciasEstudiantesController::class, 'getStudentStatistics'])->name('studentAttendance.getStatistics');

    //************************************ MANAGE MAINTENANCE ************************************//
    Route::get('/maintenance/get/all', [MantenimientosController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/get/{id}', [MantenimientosController::class, 'show'])->name('maintenance.show');
    Route::post('/maintenance/new', [MantenimientosController::class, 'store'])->name('maintenance.store');
    Route::patch('/maintenance/edit/{id}', [MantenimientosController::class, 'edit'])->name('maintenance.edit');
    Route::delete('/maintenance/delete/{id}', [MantenimientosController::class, 'destroy'])->name('maintenance.delete');
    Route::get('/maintenance/get/classroom/{id}', [MantenimientosController::class, 'getByClassroom'])->name('maintenance.getByClassroom');
    Route::get('/maintenance/get/status/{estado}', [MantenimientosController::class, 'getByStatus'])->name('maintenance.getByStatus');
    Route::get('/maintenance/get/upcoming/all', [MantenimientosController::class, 'getUpcoming'])->name('maintenance.getUpcoming');
    Route::post('/maintenance/finish/{id}', [MantenimientosController::class, 'finishMaintenance'])->name('maintenance.finish');
    Route::patch('/maintenance/change-status/{id}', [MantenimientosController::class, 'changeStatus'])->name('maintenance.changeStatus');
    Route::post('/maintenance/get/range/all', [MantenimientosController::class, 'getByDateRange'])->name('maintenance.getByDateRange');

    //************************************ MANAGE QR SCANS ************************************//
    Route::get('/qr-scans/get/all', [EscaneosQrController::class, 'index'])->name('qrScans.index');
    Route::get('/qr-scans/get/{id}', [EscaneosQrController::class, 'show'])->name('qrScans.show');
    Route::post('/qr-scans/scan', [EscaneosQrController::class, 'registerScan'])->name('qrScans.scan');
    Route::get('/qr-scans/get/classroom/{id}', [EscaneosQrController::class, 'getByClassroom'])->name('qrScans.getByClassroom');
    Route::get('/qr-scans/get/user/{id}', [EscaneosQrController::class, 'getByUser'])->name('qrScans.getByUser');
    Route::get('/qr-scans/get/session/{id}', [EscaneosQrController::class, 'getBySession'])->name('qrScans.getBySession');
    Route::get('/qr-scans/get/type/{tipo}', [EscaneosQrController::class, 'getByType'])->name('qrScans.getByType');
    Route::get('/qr-scans/get/result/{resultado}', [EscaneosQrController::class, 'getByResult'])->name('qrScans.getByResult');
    Route::get('/qr-scans/get/failed/recent', [EscaneosQrController::class, 'getRecentFailed'])->name('qrScans.getRecentFailed');
    Route::post('/qr-scans/get/range/all', [EscaneosQrController::class, 'getByDateRange'])->name('qrScans.getByDateRange');

    //************************************ MANAGE CLASSROOM HISTORY ************************************//
    Route::get('/classroom-history/get/all', [HistorialAulasController::class, 'index'])->name('classroomHistory.index');
    Route::get('/classroom-history/get/{id}', [HistorialAulasController::class, 'show'])->name('classroomHistory.show');
    Route::get('/classroom-history/get/classroom/{id}', [HistorialAulasController::class, 'getByClassroom'])->name('classroomHistory.getByClassroom');
    Route::get('/classroom-history/get/user/{id}', [HistorialAulasController::class, 'getByUser'])->name('classroomHistory.getByUser');
    Route::get('/classroom-history/get/operation/{tipo}', [HistorialAulasController::class, 'getByOperation'])->name('classroomHistory.getByOperation');
    Route::get('/classroom-history/get/field/{campo}', [HistorialAulasController::class, 'getByField'])->name('classroomHistory.getByField');
    Route::get('/classroom-history/get/recent/all', [HistorialAulasController::class, 'getRecent'])->name('classroomHistory.getRecent');
    Route::post('/classroom-history/get/range/all', [HistorialAulasController::class, 'getByDateRange'])->name('classroomHistory.getByDateRange');

    //************************************ MANAGE SYSTEM LOGS ************************************//
    Route::get('/system-logs/get/all', [SystemLogsController::class, 'index'])->name('systemLogs.index');
    Route::get('/system-logs/get/{id}', [SystemLogsController::class, 'show'])->name('systemLogs.show');
    Route::post('/system-logs/create', [SystemLogsController::class, 'store'])->name('systemLogs.store');
    Route::get('/system-logs/get/level/{nivel}', [SystemLogsController::class, 'getByLevel'])->name('systemLogs.getByLevel');
    Route::get('/system-logs/get/module/{modulo}', [SystemLogsController::class, 'getByModule'])->name('systemLogs.getByModule');
    Route::get('/system-logs/get/user/{id}', [SystemLogsController::class, 'getByUser'])->name('systemLogs.getByUser');
    Route::get('/system-logs/get/errors/all', [SystemLogsController::class, 'getErrors'])->name('systemLogs.getErrors');
    Route::get('/system-logs/get/recent/all', [SystemLogsController::class, 'getRecent'])->name('systemLogs.getRecent');
    Route::post('/system-logs/get/range/all', [SystemLogsController::class, 'getByDateRange'])->name('systemLogs.getByDateRange');
    Route::delete('/system-logs/delete/old', [SystemLogsController::class, 'deleteOldLogs'])->name('systemLogs.deleteOldLogs');

    //************************************ MANAGE NOTIFICATION TYPES ************************************//
    Route::get('/notification-types/get/all', [TiposNotificacionController::class, 'index'])->name('notificationTypes.index');
    Route::get('/notification-types/get/{id}', [TiposNotificacionController::class, 'show'])->name('notificationTypes.show');
    Route::post('/notification-types/create', [TiposNotificacionController::class, 'store'])->name('notificationTypes.store');
    Route::put('/notification-types/update/{id}', [TiposNotificacionController::class, 'update'])->name('notificationTypes.update');
    Route::delete('/notification-types/delete/{id}', [TiposNotificacionController::class, 'destroy'])->name('notificationTypes.destroy');
    Route::get('/notification-types/get/priority/{prioridad}', [TiposNotificacionController::class, 'getByPriority'])->name('notificationTypes.getByPriority');

    //************************************ MANAGE NOTIFICATIONS ************************************//
    Route::get('/notifications/get/all', [NotificacionesController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/get/{id}', [NotificacionesController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/create', [NotificacionesController::class, 'store'])->name('notifications.store');
    Route::patch('/notifications/mark-read/{id}', [NotificacionesController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/notifications/get/my/all', [NotificacionesController::class, 'getMyNotifications'])->name('notifications.getMyNotifications');
    Route::get('/notifications/get/my/unread', [NotificacionesController::class, 'getMyUnreadNotifications'])->name('notifications.getMyUnreadNotifications');
    Route::get('/notifications/get/user/{id}', [NotificacionesController::class, 'getByUser'])->name('notifications.getByUser');
    Route::get('/notifications/get/status/{estado}', [NotificacionesController::class, 'getByStatus'])->name('notifications.getByStatus');
    Route::get('/notifications/get/type/{tipo_id}', [NotificacionesController::class, 'getByType'])->name('notifications.getByType');
    Route::get('/notifications/get/pending/all', [NotificacionesController::class, 'getPending'])->name('notifications.getPending');

    //************************************ MANAGE CLASSROOM STATISTICS ************************************//
    Route::get('/classroom-stats/get/all', [EstadisticasAulasDiariasController::class, 'index'])->name('classroomStats.index');
    Route::get('/classroom-stats/get/{id}', [EstadisticasAulasDiariasController::class, 'show'])->name('classroomStats.show');
    Route::get('/classroom-stats/get/classroom/{id}', [EstadisticasAulasDiariasController::class, 'getByClassroom'])->name('classroomStats.getByClassroom');
    Route::get('/classroom-stats/get/date/{fecha}', [EstadisticasAulasDiariasController::class, 'getByDate'])->name('classroomStats.getByDate');
    Route::post('/classroom-stats/get/range/all', [EstadisticasAulasDiariasController::class, 'getByDateRange'])->name('classroomStats.getByDateRange');
    Route::get('/classroom-stats/get/top-occupied/all', [EstadisticasAulasDiariasController::class, 'getTopOccupied'])->name('classroomStats.getTopOccupied');
    Route::get('/classroom-stats/get/low-occupancy/all', [EstadisticasAulasDiariasController::class, 'getLowOccupancy'])->name('classroomStats.getLowOccupancy');
    Route::get('/classroom-stats/get/average/all', [EstadisticasAulasDiariasController::class, 'getAverageOccupancy'])->name('classroomStats.getAverageOccupancy');
    Route::get('/classroom-stats/get/total-minutes/{id}', [EstadisticasAulasDiariasController::class, 'getTotalMinutesByClassroom'])->name('classroomStats.getTotalMinutesByClassroom');

    //************************************ MANAGE SYSTEM CONFIGURATION ************************************//
    Route::get('/system-config/get/all', [ConfiguracionSistemaController::class, 'index'])->name('systemConfig.index');
    Route::get('/system-config/get/{id}', [ConfiguracionSistemaController::class, 'show'])->name('systemConfig.show');
    Route::get('/system-config/get/key/{clave}', [ConfiguracionSistemaController::class, 'getByKey'])->name('systemConfig.getByKey');
    Route::post('/system-config/create', [ConfiguracionSistemaController::class, 'store'])->name('systemConfig.store');
    Route::put('/system-config/update/{id}', [ConfiguracionSistemaController::class, 'update'])->name('systemConfig.update');
    Route::delete('/system-config/delete/{id}', [ConfiguracionSistemaController::class, 'destroy'])->name('systemConfig.destroy');
    Route::get('/system-config/get/category/{categoria}', [ConfiguracionSistemaController::class, 'getByCategory'])->name('systemConfig.getByCategory');
    Route::get('/system-config/get/modifiable/all', [ConfiguracionSistemaController::class, 'getModifiable'])->name('systemConfig.getModifiable');

    //************************************ MANAGE CARREERS ************************************//
    Route::get('/careers/get/all', [CarrerasController::class, 'index'])->name('carreras.index');
    Route::get('/careers/get/{id}', [CarrerasController::class, 'show'])->name('carreras.show');
    Route::post('/careers/new', [CarrerasController::class, 'store'])->name('carreras.store');
    Route::patch('/careers/edit/{id}', [CarrerasController::class, 'update'])->name('carreras.edit');
    Route::delete('/careers/delete/{id}', [CarrerasController::class, 'destroy'])->name('carreras.delete');
    Route::get('/careers/get/by-departament/{departamentoId}', [CarrerasController::class, 'getByDepartamento'])->name('carreras.by.departamento');
    Route::get('/careers/get/status/{estado}', [CarrerasController::class, 'getCareersByStatus'])->name('carreras.by.status');
    Route::post('/careers/disable/{id}', [CarrerasController::class, 'disableCareer'])->name('carreras.disable');
    Route::post('/careers/enable/{id}', [CarrerasController::class, 'enableCareer'])->name('carreras.enable');

    //************************************ MANAGE CLASSROOM PROBLEM REPORTS ************************************//
    Route::post('/classroom-reports/new', [ReportesProblemasAulasController::class, 'store'])->name('classroomReports.store');
    Route::get('/classroom-reports/get/all', [ReportesProblemasAulasController::class, 'index'])->name('classroomReports.index');
    Route::get('/classroom-reports/get/{id}', [ReportesProblemasAulasController::class, 'show'])->name('classroomReports.show');
    Route::get('/classroom-reports/get/classroom/{aula_id}', [ReportesProblemasAulasController::class, 'getByAula'])->name('classroomReports.getByAula');
    Route::get('/classroom-reports/get/status/{estado}', [ReportesProblemasAulasController::class, 'getByEstado'])->name('classroomReports.getByEstado');
    Route::get('/classroom-reports/get/category/{categoria}', [ReportesProblemasAulasController::class, 'getByCategoria'])->name('classroomReports.getByCategoria');
    Route::get('/classroom-reports/get/my/all', [ReportesProblemasAulasController::class, 'getMyReports'])->name('classroomReports.getMyReports');
    Route::patch('/classroom-reports/change-status/{id}', [ReportesProblemasAulasController::class, 'cambiarEstado'])->name('classroomReports.cambiarEstado');
    Route::patch('/classroom-reports/assign-user/{id}', [ReportesProblemasAulasController::class, 'asignarUsuario'])->name('classroomReports.asignarUsuario');
    Route::post('/classroom-reports/mark-resolved/{id}', [ReportesProblemasAulasController::class, 'marcarResuelto'])->name('classroomReports.marcarResuelto');
});
