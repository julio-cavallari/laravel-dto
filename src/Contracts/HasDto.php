<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Contracts;

/**
 * Has DTO Contract
 *
 * Interface for Form Requests that can be converted to DTOs.
 */
interface HasDto
{
    /**
     * Convert validated data to DTO.
     *
     * @return object The corresponding DTO instance
     */
    public function toDto(): object;

    /**
     * Get the DTO class name for this Form Request.
     *
     * @return string The fully qualified DTO class name
     */
    public function getDtoClass(): string;
}
