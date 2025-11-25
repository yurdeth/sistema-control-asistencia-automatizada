<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo de Notificaciones
 *
 * Cola de envío y registro histórico de todas las notificaciones enviadas o por enviar a los usuarios.
 * Sistema dual: BD (registro histórico) + Email (delivery vía Mailgun)
 *
 * @property int $id
 * @property int $tipo_notificacion_id
 * @property int $usuario_destino_id
 * @property string $titulo
 * @property string $mensaje
 * @property string $canal (email|push)
 * @property string $estado (pendiente|enviada|fallida|leida)
 * @property \Carbon\Carbon|null $fecha_leida
 * @property \Carbon\Carbon|null $fecha_envio
 * @property string|null $error_envio
 * @property array|null $datos_adicionales
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read tipos_notificacion $tipoNotificacion
 * @property-read usuarios $usuarioDestino
 */
class notificaciones extends Model
{
    /** @use HasFactory<\Database\Factories\NotificacionesFactory> */
    use HasFactory;

    protected $fillable = [
        'tipo_notificacion_id',
        'usuario_destino_id',
        'titulo',
        'mensaje',
        'canal',
        'estado',
        'fecha_leida',
        'fecha_envio',
        'error_envio',
        'datos_adicionales',
    ];

    protected $casts = [
        'fecha_leida' => 'datetime',
        'fecha_envio' => 'datetime',
        'datos_adicionales' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // RELACIONES
    // ========================================

    public function tipoNotificacion(): BelongsTo
    {
        return $this->belongsTo(tipos_notificacion::class, 'tipo_notificacion_id');
    }

    public function usuarioDestino(): BelongsTo
    {
        return $this->belongsTo(usuarios::class, 'usuario_destino_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Notificaciones pendientes de envío
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Notificaciones enviadas exitosamente
     */
    public function scopeEnviadas(Builder $query): Builder
    {
        return $query->where('estado', 'enviada');
    }

    /**
     * Notificaciones fallidas
     */
    public function scopeFallidas(Builder $query): Builder
    {
        return $query->where('estado', 'fallida');
    }

    /**
     * Notificaciones no leídas (enviadas pero no leídas)
     */
    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->where('estado', 'enviada')
            ->whereNull('fecha_leida');
    }

    /**
     * Notificaciones leídas
     */
    public function scopeLeidas(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_leida');
    }

    /**
     * Notificaciones por canal específico
     */
    public function scopePorCanal(Builder $query, string $canal): Builder
    {
        return $query->where('canal', $canal);
    }

    /**
     * Notificaciones de un usuario específico
     */
    public function scopeDeUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_destino_id', $usuarioId);
    }

    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================

    /**
     * Marca la notificación como leída
     */
    public function marcarComoLeida(): bool
    {
        if ($this->fecha_leida !== null) {
            return false; // Ya estaba leída
        }

        $this->fecha_leida = now();
        return $this->save();
    }

    /**
     * Marca la notificación como enviada
     */
    public function marcarComoEnviada(): bool
    {
        $this->estado = 'enviada';
        $this->fecha_envio = now();
        return $this->save();
    }

    /**
     * Marca la notificación como fallida con mensaje de error
     */
    public function marcarComoFallida(string $mensajeError): bool
    {
        $this->estado = 'fallida';
        $this->error_envio = $mensajeError;
        $this->fecha_envio = now();
        return $this->save();
    }

    /**
     * Verifica si la notificación está leída
     */
    public function estaLeida(): bool
    {
        return $this->fecha_leida !== null;
    }

    /**
     * Verifica si la notificación fue enviada exitosamente
     */
    public function fueEnviada(): bool
    {
        return $this->estado === 'enviada';
    }

    /**
     * Verifica si el envío falló
     */
    public function fallo(): bool
    {
        return $this->estado === 'fallida';
    }
}
