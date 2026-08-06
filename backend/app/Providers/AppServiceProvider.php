<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (config('database.default') === 'sqlite') {
            $sqlitePath = database_path('database.sqlite');
            if (!file_exists($sqlitePath)) {
                @mkdir(dirname($sqlitePath), 0777, true);
                @touch($sqlitePath);
            }
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('inscripciones')) {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
                }
            } catch (\Throwable $e) {
                \Log::error('SQLite auto-migration failed: ' . $e->getMessage());
            }
        }
    }
}
