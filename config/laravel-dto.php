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
    | Enum Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where Enum classes will be generated when "in" validation
    | rules are converted to enums.
    |
    */
    'enum_namespace' => 'App\\Enums',

    /*
    |--------------------------------------------------------------------------
    | Enum Output Directory
    |--------------------------------------------------------------------------
    |
    | The directory where Enum files will be created. This path is relative
    | to the Laravel application's base path.
    |
    */
    'enum_output_path' => 'app/Enums',

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
        // Boolean rules
        'accepted' => 'bool',
        'accepted_if' => 'bool',
        'boolean' => 'bool',
        'declined' => 'bool',
        'declined_if' => 'bool',

        // String rules
        'active_url' => 'string',
        'alpha' => 'string',
        'alpha_dash' => 'string',
        'alpha_num' => 'string',
        'ascii' => 'string',
        'confirmed' => 'string',
        'current_password' => 'string',
        'different' => 'string',
        'doesnt_start_with' => 'string',
        'doesnt_end_with' => 'string',
        'email' => 'string',
        'ends_with' => 'string',
        'hex_color' => 'string',
        'lowercase' => 'string',
        'mac_address' => 'string',
        'not_regex' => 'string',
        'regex' => 'string',
        'same' => 'string',
        'starts_with' => 'string',
        'string' => 'string',
        'uppercase' => 'string',
        'url' => 'string',
        'ulid' => 'string',
        'uuid' => 'string',

        // Numeric rules
        'between' => 'float',
        'decimal' => 'float',
        'digits' => 'int',
        'digits_between' => 'int',
        'gt' => 'float',
        'gte' => 'float',
        'integer' => 'int',
        'lt' => 'float',
        'lte' => 'float',
        'max' => 'float',
        'max_digits' => 'int',
        'min' => 'float',
        'min_digits' => 'int',
        'multiple_of' => 'float',
        'numeric' => 'float',
        'size' => 'float',

        // Array rules
        'array' => 'array',
        'contains' => 'array',
        'distinct' => 'array',
        'in' => 'string', // Value must be in array, but field itself is usually string
        'in_array' => 'string',
        'list' => 'array',
        'not_in' => 'string',
        'required_array_keys' => 'array',

        // Date/Time rules
        'after' => 'Carbon\\Carbon',
        'after_or_equal' => 'Carbon\\Carbon',
        'before' => 'Carbon\\Carbon',
        'before_or_equal' => 'Carbon\\Carbon',
        'date' => 'Carbon\\Carbon',
        'date_equals' => 'Carbon\\Carbon',
        'date_format' => 'Carbon\\Carbon',
        'timezone' => 'string',

        // File rules
        'dimensions' => 'Illuminate\\Http\\UploadedFile',
        'extensions' => 'Illuminate\\Http\\UploadedFile',
        'file' => 'Illuminate\\Http\\UploadedFile',
        'image' => 'Illuminate\\Http\\UploadedFile',
        'mimes' => 'Illuminate\\Http\\UploadedFile',
        'mimetypes' => 'Illuminate\\Http\\UploadedFile',

        // Database rules
        'exists' => 'mixed', // Can be string, int, etc. depending on column type
        'unique' => 'mixed', // Can be string, int, etc. depending on column type

        // Network/IP rules
        'ip' => 'string',
        'ipv4' => 'string',
        'ipv6' => 'string',

        // JSON rule
        'json' => 'array',

        // Enum rule
        'enum' => 'mixed', // Type depends on the enum

        // Common aliases and variations
        'bool' => 'bool',
        'int' => 'int',
        'float' => 'float',

        // Note: Utility rules like 'required', 'nullable', 'filled', etc. are intentionally
        // excluded because they define field presence/optionality, not data types.
        // These are handled separately in the parsing logic.
    ],
];
