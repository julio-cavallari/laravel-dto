<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Tests;

use JulioCavallari\LaravelDto\LaravelDtoServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDtoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laravel-dto.namespace', 'App\\DTOs');
        $app['config']->set('laravel-dto.output_path', 'app/DTOs');
        $app['config']->set('laravel-dto.form_request_path', 'app/Http/Requests');
        $app['config']->set('laravel-dto.form_request_namespace', 'App\\Http\\Requests');
    }
}
