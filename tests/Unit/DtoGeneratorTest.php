<?php

declare(strict_types=1);

use JulioCavallari\LaravelDto\Generators\DtoGenerator;

beforeEach(function (): void {
    $this->generator = new DtoGenerator;
});

it('generates basic DTO code', function (): void {
    $parsedData = [
        'class_name' => 'CreateUserRequest',
        'namespace' => 'App\\Http\\Requests',
        'fields' => [
            [
                'name' => 'name',
                'type' => 'string',
                'nullable' => false,
                'is_array' => false,
                'has_default' => false,
                'default_value' => null,
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'email',
                'type' => 'string',
                'nullable' => false,
                'is_array' => false,
                'has_default' => false,
                'default_value' => null,
                'rules' => ['required', 'email'],
            ],
        ],
        'rules' => [
            'name' => 'required|string',
            'email' => 'required|email',
        ],
    ];

    $result = $this->generator->generate($parsedData);

    expect($result)->toContain('final readonly class CreateUserData');
    expect($result)->toContain('public readonly string $name');
    expect($result)->toContain('public readonly string $email');
    expect($result)->toContain('fromRequest(CreateUserRequest $request)');
});

it('generates DTO with nullable fields', function (): void {
    $parsedData = [
        'class_name' => 'UpdateUserRequest',
        'namespace' => 'App\\Http\\Requests',
        'fields' => [
            [
                'name' => 'name',
                'type' => 'string',
                'nullable' => true,
                'is_array' => false,
                'has_default' => true,
                'default_value' => null,
                'rules' => ['nullable', 'string'],
            ],
        ],
        'rules' => [
            'name' => 'nullable|string',
        ],
    ];

    $result = $this->generator->generate($parsedData);

    expect($result)->toContain('public readonly ?string $name = null');
});

it('generates DTO path correctly', function (): void {
    $path = $this->generator->getDtoPath('CreateUserRequest');

    expect($path)->toContain('CreateUserData.php');
});
