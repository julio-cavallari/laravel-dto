<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto;

use Illuminate\Support\ServiceProvider;
use JulioCavallari\LaravelDto\Commands\CheckDtoCommand;
use JulioCavallari\LaravelDto\Commands\GenerateDtoCommand;

/**
 * Laravel DTO Service Provider
 *
 * Registers the DTO generation command and publishes configuration files.
 */
class LaravelDtoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-dto.php',
            'laravel-dto'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateDtoCommand::class,
                CheckDtoCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/laravel-dto.php' => config_path('laravel-dto.php'),
            ], 'laravel-dto-config');
        }
    }
}
