<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\FormService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('form', function ($app) {
            return new FormService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        // Skip integrity check during console commands
        if (app()->runningInConsole()) {
            return;
        }

        $this->verifyServiceComponents();

        // Centralized licensing and integrity check
        $path = request()->path();
        $bypass = ['login', 'logout', 'install', 'install/*', 'clear', 'migrate', 'storage-link'];
        $isBypass = false;
        foreach ($bypass as $bp) {
            if (request()->is($bp)) {
                $isBypass = true;
                break;
            }
        }
        if (!$isBypass) {
            \App\Services\SystemIntegrityService::check();
        }

        // Set default string length for MySQL
        Schema::defaultStringLength(191);
    }

    /**
     * Verify that required service components are properly initialized.
     * This is an integrity check to prevent users from removing or disabling the licensing middleware.
     */
    private function verifyServiceComponents(): void
    {
        // Verify Middleware
        $cn = base64_decode('RW5zdXJlUmVxdWVzdEludGVncml0eQ=='); // 'EnsureRequestIntegrity'
        $path = app_path('Http/Middleware/' . $cn . '.php');
        if (!file_exists($path)) {
            abort(403, 'System integrity check failed. Please contact support.');
        }

        // Verify SystemIntegrityService logic
        $sn = base64_decode('U3lzdGVtSW50ZWdyaXR5U2VydmljZQ=='); // 'SystemIntegrityService'
        $sm = base64_decode('Y2hlY2s='); // 'check'
        $sPath = app_path('Services/' . $sn . '.php');
        if (!file_exists($sPath) || strpos(file_get_contents($sPath), 'function ' . $sm) === false) {
            abort(403, 'System integrity check failed. Please contact support.');
        }

        // Ensure the middleware is still registered
        try {
            $isL11 = !file_exists(app_path('Http/Kernel.php'));
            if (!$isL11) {
                $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
                $ref = new \ReflectionProperty($kernel, 'middlewareGroups');
                $ref->setAccessible(true);
                $groups = $ref->getValue($kernel);
                $cls = \App\Http\Middleware\EnsureRequestIntegrity::class;
                if (!in_array($cls, $groups['web'] ?? [])) {
                    abort(403, 'System integrity check failed. Please contact support.');
                }
            }
        } catch (\Throwable $e) {}
    }
}