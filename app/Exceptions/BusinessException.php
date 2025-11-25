<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Clase base para todas las excepciones de lógica de negocio
 *
 * Las excepciones de negocio representan errores esperados en el flujo de la aplicación
 * (validaciones, permisos, estados inválidos, etc.) y siempre retornan códigos HTTP 4XX.
 *
 * @package App\Exceptions
 * @author Sistema de Control de Asistencia
 * @version 1.0.0
 */
abstract class BusinessException extends Exception
{
    /**
     * Código de error único para esta excepción
     * Formato: CATEGORY_SPECIFIC_ERROR
     * Ejemplo: AUTH_UNAUTHORIZED, GROUP_FULL, SCHEDULE_CONFLICT
     *
     * @var string
     */
    protected string $errorCode;

    /**
     * Código HTTP a retornar
     * Valores comunes:
     * - 400: Bad Request (input inválido)
     * - 401: Unauthorized (no autenticado)
     * - 403: Forbidden (sin permisos)
     * - 404: Not Found (recurso no existe)
     * - 409: Conflict (conflicto de estado)
     * - 422: Unprocessable Entity (validación de negocio)
     *
     * @var int
     */
    protected int $httpStatus = 400;

    /**
     * Datos adicionales para debugging y contexto
     * Se incluye solo en modo debug (APP_DEBUG=true)
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Si se debe loguear automáticamente esta excepción
     *
     * @var bool
     */
    protected bool $shouldLog = true;

    /**
     * Constructor
     *
     * @param string $message Mensaje descriptivo para el usuario
     * @param string|null $errorCode Código de error único (opcional si definido en clase)
     * @param int|null $httpStatus Código HTTP (opcional si definido en clase)
     * @param array $context Datos adicionales para debugging
     * @param \Throwable|null $previous Excepción previa (para chaining)
     */
    public function __construct(
        string $message,
        ?string $errorCode = null,
        ?int $httpStatus = null,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode ?? $this->errorCode ?? 'BUSINESS_ERROR';
        $this->httpStatus = $httpStatus ?? $this->httpStatus;
        $this->context = $context;
    }

    /**
     * Renderizar como respuesta JSON
     *
     * Este método se llama automáticamente por Laravel cuando la excepción
     * es lanzada en un contexto de API (request con Accept: application/json)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        // Incluir contexto solo en desarrollo
        if (config('app.debug') && !empty($this->context)) {
            $response['debug'] = $this->context;
        }

        // Agregar timestamp para tracking
        $response['timestamp'] = now()->toIso8601String();

        return response()->json($response, $this->httpStatus);
    }

    /**
     * Reportar la excepción al sistema de logging
     *
     * Este método se llama automáticamente antes de render()
     * Se puede sobrescribir en clases hijas para logging personalizado
     *
     * @return void
     */
    public function report(): void
    {
        if (!$this->shouldLog) {
            return;
        }

        // Log de nivel WARNING para excepciones de negocio (no son errores críticos)
        Log::warning("Business Exception: {$this->errorCode}", [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'http_status' => $this->httpStatus,
            'context' => $this->context,
            'user_id' => auth()->id() ?? null,
            'request_url' => request()->fullUrl() ?? null,
            'request_method' => request()->method() ?? null,
            'ip_address' => request()->ip() ?? null,
            'trace' => config('app.debug') ? $this->getTraceAsString() : null,
        ]);
    }

    /**
     * Obtener el código de error
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Obtener el código HTTP
     *
     * @return int
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Obtener el contexto
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Deshabilitar logging para esta excepción
     *
     * @return static
     */
    public function withoutLogging(): static
    {
        $this->shouldLog = false;
        return $this;
    }
}
