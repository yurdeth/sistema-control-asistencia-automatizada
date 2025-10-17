<?php

namespace App\Policies;

use App\Models\User;
use App\Models\escaneos_qr;
use Illuminate\Auth\Access\Response;

class EscaneosQrPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, escaneos_qr $escaneosQr): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, escaneos_qr $escaneosQr): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, escaneos_qr $escaneosQr): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, escaneos_qr $escaneosQr): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, escaneos_qr $escaneosQr): bool
    {
        return false;
    }
}
