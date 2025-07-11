<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | DTO Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where DTOs will be generated. This will be used to create
    | the proper namespace declaration in generated DTO classes.
    |
    */
    'namespace' => 'App\\DTOs',

    /*
    |--------------------------------------------------------------------------
    | DTO Output Directory
    |--------------------------------------------------------------------------
    |
    | The directory where DTO files will be created. This path is relative
    | to the Laravel application's base path.
    |
    */
    'output_path' => 'app/DTOs',

    /*
    |--------------------------------------------------------------------------
    | Form Request Directory
    |--------------------------------------------------------------------------
    |
    | The directory where Form Request classes are located. This is where
    | the command will scan for Form Request classes to generate DTOs from.
    |
    */
    'form_request_path' => 'app/Http/Requests',

    /*
    |--------------------------------------------------------------------------
    | Form Request Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where Form Request classes are located.
    |
    */
    'form_request_namespace' => 'App\\Http\\Requests',

    /*
    |--------------------------------------------------------------------------
    | DTO Class Suffix
    |--------------------------------------------------------------------------
    |
    | The suffix to append to DTO class names. For example, if set to 'Data',
    | CreateArticleRequest will generate CreateArticleData.
    |
    */
    'dto_suffix' => 'Data',

    /*
    |--------------------------------------------------------------------------
    | Form Request Suffix
    |--------------------------------------------------------------------------
    |
    | The suffix that Form Request classes use. This will be stripped when
    | generating DTO class names.
    |
    */
    'form_request_suffix' => 'Request',

    /*
    |--------------------------------------------------------------------------
    | Excluded Form Requests
    |--------------------------------------------------------------------------
    |
    | Form Request classes that should be excluded from DTO generation.
    | You can specify class names or patterns.
    |
    */
    'excluded_requests' => [
        // 'LoginRequest',
        // '*AuthRequest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generate Readonly DTOs
    |--------------------------------------------------------------------------
    |
    | Whether to generate DTOs with readonly properties. This makes DTOs
    | immutable and is recommended for most use cases.
    |
    */
    'readonly' => true,

    /*
    |--------------------------------------------------------------------------
    | Generate fromRequest Method
    |--------------------------------------------------------------------------
    |
    | Whether to generate a static fromRequest method that creates a DTO
    | instance from a Form Request object.
    |
    */
    'generate_from_request_method' => true,

    /*
    |--------------------------------------------------------------------------
    | Type Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping of Laravel validation rules to PHP types. This is used to
    | determine the appropriate type hint for DTO properties.
    |
    */
    'type_mapping' => [
        'string' => 'string',
        'integer' => 'int',
        'numeric' => 'float',
        'boolean' => 'bool',
        'array' => 'array',
        'file' => 'Illuminate\\Http\\UploadedFile',
        'image' => 'Illuminate\\Http\\UploadedFile',
        'date' => 'Carbon\\Carbon',
        'email' => 'string',
        'url' => 'string',
        'uuid' => 'string',
        'json' => 'array',
    ],
];
