<?php

namespace App\Providers;

use App\Models\inscripciones;
use App\Models\sesiones_clase;
use App\Observers\InscripcionObserver;
use App\Observers\SesionClaseObserver;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Vite::prefetch(concurrency: 3);

        Passport::tokensExpireIn(CarbonInterval::days(30));
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::days(30));

        // ========================================
        // REGISTRAR OBSERVERS
        // ========================================
        // Estos observers reemplazan triggers SQL para mejor testeo y mantenibilidad

        // InscripcionObserver: Mantiene contador de estudiantes_inscritos en grupos
        // Reemplaza: trg_incrementar_inscritos y trg_decrementar_inscritos
        inscripciones::observe(InscripcionObserver::class);

        // SesionClaseObserver: Calcula duración y retraso automáticamente
        // Reemplaza: trg_calcular_duracion_y_retraso
        sesiones_clase::observe(SesionClaseObserver::class);
    }
}
