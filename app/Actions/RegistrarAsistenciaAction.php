<?php

namespace App\Actions;

use App\Exceptions\Business\Asistencia\EstudianteNoInscritoException;
use App\Exceptions\Business\Asistencia\QRInvalidoException;
use App\Exceptions\Business\Asistencia\SesionNoActivaException;
use App\Models\asistencias_estudiantes;
use App\Models\aulas;
use App\Models\escaneos_qr;
use App\Models\inscripciones;
use App\Models\sesiones_clase;
use App\Models\usuarios;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action para registrar asistencia de un estudiante
 *
 * Reemplaza el stored procedure: sp_registrar_asistencia_estudiante
 *
 * Funcionalidad:
 * 1. Valida el código QR del aula
 * 2. Verifica que existe una sesión activa en el aula
 * 3. Valida que el estudiante esté inscrito en el grupo
 * 4. Registra la asistencia (idempotente - no duplica)
 * 5. Registra el escaneo del QR para auditoría
 *
 * @package App\Actions
 * @version 1.0.0
 */
class RegistrarAsistenciaAction
{
    /**
     * Ejecutar acción de registrar asistencia
     *
     * @param string $codigoQr Código QR escaneado del aula
     * @param int $estudianteId ID del estudiante
     * @param array $datosAdicionales Datos opcionales (latitud, longitud, etc.)
     * @return array Datos de la asistencia registrada
     * @throws QRInvalidoException Si el QR no es válido
     * @throws SesionNoActivaException Si no hay sesión activa
     * @throws EstudianteNoInscritoException Si el estudiante no está inscrito
     */
    public function execute(string $codigoQr, int $estudianteId, array $datosAdicionales = []): array
    {
        // 1. Validar código QR y obtener aula
        $aula = aulas::where('qr_code', $codigoQr)->first();

        if (!$aula) {
            // Registrar intento fallido
            $this->registrarEscaneoFallido($codigoQr, $estudianteId, 'QR_NO_RECONOCIDO');

            throw QRInvalidoException::noReconocido($codigoQr);
        }

        // 2. Buscar sesión activa en el aula para hoy
        $sesion = sesiones_clase::with(['horario.grupo.materia'])
            ->whereHas('horario', function ($q) use ($aula) {
                $q->where('aula_id', $aula->id);
            })
            ->where('estado', 'en_curso')
            ->whereDate('fecha_clase', now()->toDateString())
            ->first();

        if (!$sesion) {
            // Registrar intento fallido
            $this->registrarEscaneoFallido($codigoQr, $estudianteId, 'SIN_SESION_ACTIVA', $aula->id);

            throw SesionNoActivaException::enAula($aula->id, $aula->nombre);
        }

        // 3. Validar que el estudiante esté inscrito en el grupo
        $estudiante = usuarios::findOrFail($estudianteId);

        $inscrito = inscripciones::where('estudiante_id', $estudianteId)
            ->where('grupo_id', $sesion->horario->grupo_id)
            ->exists();

        if (!$inscrito) {
            // Registrar intento fallido
            $this->registrarEscaneoFallido(
                $codigoQr,
                $estudianteId,
                'ESTUDIANTE_NO_INSCRITO',
                $aula->id,
                $sesion->id
            );

            throw EstudianteNoInscritoException::enGrupo(
                $estudiante->id,
                $estudiante->nombre_completo,
                $sesion->horario->grupo_id,
                $sesion->horario->grupo->nombre,
                $sesion->horario->grupo->materia->nombre
            );
        }

        // 4. Registrar asistencia (idempotente) en transacción
        return DB::transaction(function () use ($sesion, $estudianteId, $estudiante, $codigoQr, $datosAdicionales) {
            // Usar updateOrCreate para evitar duplicados (idempotente)
            $asistencia = asistencias_estudiantes::updateOrCreate(
                [
                    'sesion_clase_id' => $sesion->id,
                    'estudiante_id' => $estudianteId
                ],
                [
                    'hora_registro' => now(),
                    'metodo_registro' => 'qr',
                    'latitud' => $datosAdicionales['latitud'] ?? null,
                    'longitud' => $datosAdicionales['longitud'] ?? null
                ]
            );

            $esNuevaAsistencia = $asistencia->wasRecentlyCreated;

            // Registrar escaneo exitoso
            $this->registrarEscaneoExitoso(
                $codigoQr,
                $estudianteId,
                $sesion->horario->aula_id,
                $sesion->id,
                $asistencia->id
            );

            // Log de auditoría
            Log::info($esNuevaAsistencia ? 'Asistencia registrada' : 'Asistencia actualizada', [
                'asistencia_id' => $asistencia->id,
                'sesion_id' => $sesion->id,
                'estudiante_id' => $estudianteId,
                'grupo_id' => $sesion->horario->grupo_id,
                'materia_id' => $sesion->horario->grupo->materia_id,
                'aula_id' => $sesion->horario->aula_id,
                'hora_registro' => $asistencia->hora_registro->format('H:i:s'),
                'metodo' => 'qr',
                'es_nueva' => $esNuevaAsistencia
            ]);

            return [
                'asistencia_id' => $asistencia->id,
                'sesion_id' => $sesion->id,
                'estudiante' => [
                    'id' => $estudiante->id,
                    'nombre_completo' => $estudiante->nombre_completo,
                    'codigo' => $estudiante->codigo ?? null
                ],
                'materia' => [
                    'id' => $sesion->horario->grupo->materia->id,
                    'nombre' => $sesion->horario->grupo->materia->nombre
                ],
                'grupo' => [
                    'id' => $sesion->horario->grupo->id,
                    'nombre' => $sesion->horario->grupo->nombre
                ],
                'aula' => [
                    'id' => $sesion->horario->aula->id,
                    'nombre' => $sesion->horario->aula->nombre,
                    'codigo' => $sesion->horario->aula->codigo
                ],
                'hora_clase_inicio' => $sesion->hora_inicio_real->format('H:i:s'),
                'hora_registro' => $asistencia->hora_registro->format('H:i:s'),
                'metodo_registro' => 'qr',
                'es_nueva' => $esNuevaAsistencia,
                'mensaje' => $esNuevaAsistencia
                    ? 'Asistencia registrada correctamente'
                    : 'Asistencia actualizada (ya estabas registrado)'
            ];
        });
    }

    /**
     * Registrar escaneo exitoso de QR
     *
     * @param string $codigoQr
     * @param int $estudianteId
     * @param int $aulaId
     * @param int $sesionId
     * @param int $asistenciaId
     * @return void
     */
    private function registrarEscaneoExitoso(
        string $codigoQr,
        int $estudianteId,
        int $aulaId,
        int $sesionId,
        int $asistenciaId
    ): void {
        escaneos_qr::create([
            'codigo_qr' => $codigoQr,
            'usuario_id' => $estudianteId,
            'aula_id' => $aulaId,
            'sesion_clase_id' => $sesionId,
            'asistencia_estudiante_id' => $asistenciaId,
            'exitoso' => true,
            'razon_fallo' => null,
            'fecha_hora_escaneo' => now()
        ]);
    }

    /**
     * Registrar intento fallido de escaneo QR
     *
     * @param string $codigoQr
     * @param int $estudianteId
     * @param string $razonFallo
     * @param int|null $aulaId
     * @param int|null $sesionId
     * @return void
     */
    private function registrarEscaneoFallido(
        string $codigoQr,
        int $estudianteId,
        string $razonFallo,
        ?int $aulaId = null,
        ?int $sesionId = null
    ): void {
        escaneos_qr::create([
            'codigo_qr' => $codigoQr,
            'usuario_id' => $estudianteId,
            'aula_id' => $aulaId,
            'sesion_clase_id' => $sesionId,
            'asistencia_estudiante_id' => null,
            'exitoso' => false,
            'razon_fallo' => $razonFallo,
            'fecha_hora_escaneo' => now()
        ]);
    }

    /**
     * Verificar si un estudiante puede registrar asistencia
     *
     * Útil para validar antes de intentar el registro
     *
     * @param string $codigoQr
     * @param int $estudianteId
     * @return array ['puede_registrar' => bool, 'razon' => string|null, 'sesion_id' => int|null]
     */
    public function verificarDisponibilidad(string $codigoQr, int $estudianteId): array
    {
        try {
            // Validar QR
            $aula = aulas::where('qr_code', $codigoQr)->first();
            if (!$aula) {
                return [
                    'puede_registrar' => false,
                    'razon' => 'Código QR no válido',
                    'sesion_id' => null
                ];
            }

            // Buscar sesión activa
            $sesion = sesiones_clase::whereHas('horario', fn($q) => $q->where('aula_id', $aula->id))
                ->where('estado', 'en_curso')
                ->whereDate('fecha_clase', now()->toDateString())
                ->first();

            if (!$sesion) {
                return [
                    'puede_registrar' => false,
                    'razon' => 'No hay clase activa en este aula',
                    'sesion_id' => null
                ];
            }

            // Validar inscripción
            $inscrito = inscripciones::where('estudiante_id', $estudianteId)
                ->where('grupo_id', $sesion->horario->grupo_id)
                ->exists();

            if (!$inscrito) {
                return [
                    'puede_registrar' => false,
                    'razon' => 'No estás inscrito en este grupo',
                    'sesion_id' => $sesion->id
                ];
            }

            // Ya registrado
            $yaRegistrado = asistencias_estudiantes::where('sesion_clase_id', $sesion->id)
                ->where('estudiante_id', $estudianteId)
                ->exists();

            if ($yaRegistrado) {
                return [
                    'puede_registrar' => false,
                    'razon' => 'Ya tienes asistencia registrada en esta clase',
                    'sesion_id' => $sesion->id
                ];
            }

            return [
                'puede_registrar' => true,
                'razon' => null,
                'sesion_id' => $sesion->id
            ];

        } catch (\Exception $e) {
            return [
                'puede_registrar' => false,
                'razon' => 'Error al verificar: ' . $e->getMessage(),
                'sesion_id' => null
            ];
        }
    }
}
