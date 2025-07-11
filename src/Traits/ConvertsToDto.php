<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Traits;

use JulioCavallari\LaravelDto\Attributes\UseDto;
use ReflectionClass;
use RuntimeException;

/**
 * Converts To DTO Trait
 *
 * Provides functionality to convert Form Request validated data to DTOs.
 */
trait ConvertsToDto
{
    /**
     * Convert validated data to DTO.
     *
     * This method creates a DTO instance using only validated data from the Form Request.
     * The Laravel Form Request validation has already run before this method is called.
     *
     * @return object The corresponding DTO instance
     *
     * @throws RuntimeException If DTO class is not found or doesn't have fromRequest method
     */
    public function toDto(): object
    {
        $dtoClass = $this->getDtoClass();

        if (! class_exists($dtoClass)) {
            throw new RuntimeException("DTO class [{$dtoClass}] not found. Run 'php artisan dto:generate' to create it.");
        }

        if (! method_exists($dtoClass, 'fromRequest')) {
            throw new RuntimeException("DTO class [{$dtoClass}] must have a 'fromRequest' method.");
        }

        // Ensure validation has been run (in case someone calls this manually)
        // This is usually automatic when injected into controller methods
        if (! $this->hasValidated()) {
            $this->validateResolved();
        }

        return $dtoClass::fromRequest($this);
    }

    /**
     * Check if the request has been validated.
     */
    private function hasValidated(): bool
    {
        try {
            // Try to access validated data - if no exception, it means validation ran
            $this->validated();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the DTO class name for this Form Request.
     *
     * @return string The fully qualified DTO class name
     */
    public function getDtoClass(): string
    {
        // Check if UseDto attribute is present
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(UseDto::class);

        if ($attributes !== []) {
            $attribute = $attributes[0]->newInstance();

            return $attribute->dtoClass;
        }

        // Fall back to convention-based naming
        $requestClass = static::class;
        $baseName = class_basename($requestClass);
        $namespace = $this->getDtoNamespace();

        // Convert CreateArticleRequest to CreateArticleData
        $dtoName = $this->convertRequestNameToDto($baseName);

        return "{$namespace}\\{$dtoName}";
    }

    /**
     * Get the DTO namespace.
     */
    private function getDtoNamespace(): string
    {
        return config('laravel-dto.namespace', 'App\\DTOs');
    }

    /**
     * Convert Form Request name to DTO name.
     */
    private function convertRequestNameToDto(string $requestName): string
    {
        $requestSuffix = config('laravel-dto.form_request_suffix', 'Request');
        $dtoSuffix = config('laravel-dto.dto_suffix', 'Data');

        if (str_ends_with($requestName, (string) $requestSuffix)) {
            $baseName = substr($requestName, 0, -strlen((string) $requestSuffix));
        } else {
            $baseName = $requestName;
        }

        return $baseName.$dtoSuffix;
    }
}
