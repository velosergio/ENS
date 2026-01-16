<?php

namespace App\Providers;

use App\Services\PermissionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureGates(): void
    {
        $permissionService = app(PermissionService::class);

        // Crear Gate dinámico usando before para verificar permisos
        // Uso: Gate::allows('module:module.action') o @can('module:module.action')
        Gate::before(function ($user, $ability) use ($permissionService) {
            // Si la habilidad sigue el formato 'module:module.action'
            if (str_starts_with($ability, 'module:')) {
                $parts = explode(':', $ability);
                if (count($parts) === 2) {
                    $moduleAction = explode('.', $parts[1]);
                    if (count($moduleAction) === 2) {
                        [$module, $action] = $moduleAction;

                        return $permissionService->hasPermission($user, $module, $action);
                    }
                }
            }

            return null; // Dejar que otros Gates se ejecuten
        });

        // Helper Gate: verificar rol
        Gate::define('role.mango', fn ($user) => $user->esMango());
        Gate::define('role.admin', fn ($user) => $user->esAdmin() || $user->esMango());
        Gate::define('role.equipista', fn ($user) => $user->esEquipista() || $user->esAdmin() || $user->esMango());
    }
}
