<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo de Tipos de Notificación
 *
 * Catálogo que define las plantillas y propiedades de cada tipo de notificación que el sistema puede enviar.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $prioridad (baja|media|alta|urgente)
 * @property string $estado (activo|inactivo)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|notificaciones[] $notificaciones
 */
class tipos_notificacion extends Model
{
    /** @use HasFactory<\Database\Factories\TiposNotificacionFactory> */
    use HasFactory;

    protected $table = 'tipos_notificacion';

    protected $fillable = [
        'nombre',
        'descripcion',
        'prioridad',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // RELACIONES
    // ========================================

    public function notificaciones(): HasMany
    {
        return $this->hasMany(notificaciones::class, 'tipo_notificacion_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Tipos de notificación activos
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Tipos de notificación inactivos
     */
    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('estado', 'inactivo');
    }

    /**
     * Tipos de notificación por prioridad
     */
    public function scopePorPrioridad(Builder $query, string $prioridad): Builder
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Tipos de notificación de alta prioridad
     */
    public function scopeAltaPrioridad(Builder $query): Builder
    {
        return $query->whereIn('prioridad', ['alta', 'urgente']);
    }

    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================

    /**
     * Verifica si el tipo está activo
     */
    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Verifica si es de alta prioridad
     */
    public function esAltaPrioridad(): bool
    {
        return in_array($this->prioridad, ['alta', 'urgente']);
    }
}
