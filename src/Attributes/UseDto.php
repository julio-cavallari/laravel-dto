<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Attributes;

use Attribute;

/**
 * Use DTO Attribute
 *
 * Specifies which DTO class to use for a Form Request.
 * If not specified, the package will use the default naming convention.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class UseDto
{
    /**
     * Create a new UseDto attribute instance.
     *
     * @param  class-string  $dtoClass  The fully qualified DTO class name
     */
    public function __construct(
        public readonly string $dtoClass
    ) {}
}
