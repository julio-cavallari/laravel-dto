<?php

declare(strict_types=1);

if (! function_exists('config')) {
    /**
     * Get configuration value.
     */
    function config(string $key, mixed $default = null): mixed
    {
        // For standalone usage, return default values
        static $config = null;

        if ($config === null) {
            $config = [
                'laravel-dto.type_mapping' => [
                    'string' => 'string',
                    'integer' => 'int',
                    'numeric' => 'float',
                    'boolean' => 'bool',
                    'array' => 'array',
                    'email' => 'string',
                ],
                'laravel-dto.namespace' => 'App\\DTOs',
                'laravel-dto.output_path' => 'app/DTOs',
                'laravel-dto.form_request_suffix' => 'Request',
                'laravel-dto.dto_suffix' => 'Data',
                'laravel-dto.readonly' => true,
                'laravel-dto.generate_from_request_method' => true,
            ];
        }

        return $config[$key] ?? $default;
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     */
    function base_path(string $path = ''): string
    {
        $basePath = getcwd();

        return $basePath.($path !== '' && $path !== '0' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     */
    function config_path(string $path = ''): string
    {
        $configPath = getcwd().DIRECTORY_SEPARATOR.'config';

        return $configPath.($path !== '' && $path !== '0' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
