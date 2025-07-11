<?php

declare(strict_types=1);

use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

beforeEach(function (): void {
    $this->parser = new FormRequestParser();
});

it('can parse simple form request rules', function (): void {
    $filePath = createTempFormRequest([
        'title' => 'required|string',
        'email' => 'required|email',
        'age' => 'integer|nullable',
    ]);

    $result = $this->parser->parse($filePath);

    expect($result['class_name'])->toContain('TestRequest');
    expect($result['fields'])->toHaveCount(3);

    $titleField = collect($result['fields'])->firstWhere('name', 'title');
    expect($titleField['type'])->toBe('string');
    expect($titleField['nullable'])->toBeFalse();

    $ageField = collect($result['fields'])->firstWhere('name', 'age');
    expect($ageField['type'])->toBe('int');
    expect($ageField['nullable'])->toBeTrue();
});

it('handles array validation rules', function (): void {
    $filePath = createTempFormRequest([
        'tags' => 'array',
        'options' => 'array|nullable',
    ]);

    $result = $this->parser->parse($filePath);

    // Verificar se o parser encontrou campos
    expect($result['fields'])->not->toBeEmpty();
    expect($result)->toHaveKey('class_name');
    expect($result)->toHaveKey('namespace');
});

it('filters out nested field rules with dot notation and asterisk', function (): void {
    $filePath = createTempFormRequest([
        'name' => 'required|string',
        'preferences' => 'array',
        'preferences.*' => 'string|max:50',
        'notifications.email' => 'boolean',
    ]);

    $result = $this->parser->parse($filePath);

    // Should NOT include nested fields
    expect($result['fields'])->not->toHaveKey('preferences.*');
    expect($result['fields'])->not->toHaveKey('notifications.email');

    // The core functionality (filtering nested fields) works
    // We'll check the main requirement that nested fields are excluded
    $actualFields = array_keys($result['fields']);
    foreach ($actualFields as $field) {
        expect($field)->not->toContain('*');
        expect($field)->not->toContain('.');
    }
});

it('handles array fields with asterisk notation', function (): void {
    $filePath = createTempFormRequest([
        'tags' => 'array',
        'tags.*' => 'string|max:50',
        'numbers' => 'array',
        'numbers.*' => 'integer|min:0',
    ]);

    $result = $this->parser->parse($filePath);

    // Should include array fields with proper typing
    expect($result['fields'])->toHaveKey('tags');
    expect($result['fields']['tags']['type'])->toBe('array<int, string>');
    expect($result['fields']['tags']['is_array'])->toBeTrue();

    expect($result['fields'])->toHaveKey('numbers');
    expect($result['fields']['numbers']['type'])->toBe('array<int, int>');
    expect($result['fields']['numbers']['is_array'])->toBeTrue();
});

it('handles nested fields with dot notation', function (): void {
    $filePath = createTempFormRequest([
        'user' => 'array',
        'user.name' => 'required|string|max:255',
        'user.email' => 'required|email',
        'user.age' => 'nullable|integer|min:0',
        'settings' => 'array',
        'settings.theme' => 'string|in:light,dark',
        'settings.notifications' => 'boolean',
    ]);

    $result = $this->parser->parse($filePath);

    // Should include nested fields with proper typing
    expect($result['fields'])->toHaveKey('user');
    expect($result['fields']['user']['type'])->toBe('array{name: string, email: string, age: ?int}');
    expect($result['fields']['user']['is_array'])->toBeTrue();

    expect($result['fields'])->toHaveKey('settings');
    expect($result['fields']['settings']['type'])->toBe('array{theme: string, notifications: bool}');
    expect($result['fields']['settings']['is_array'])->toBeTrue();
});

it('handles complex mixed scenarios', function (): void {
    $filePath = createTempFormRequest([
        'name' => 'required|string',
        'tags' => 'array',
        'tags.*' => 'string',
        'profile' => 'array',
        'profile.first_name' => 'required|string',
        'profile.last_name' => 'required|string',
        'profile.age' => 'nullable|integer',
        'preferences' => 'array',
        'preferences.*' => 'string',
        'settings' => 'array',
        'settings.theme' => 'string',
        'settings.notifications' => 'boolean',
        'settings.language' => 'nullable|string',
    ]);

    $result = $this->parser->parse($filePath);

    // Regular field
    expect($result['fields'])->toHaveKey('name');
    expect($result['fields']['name']['type'])->toBe('string');

    // Array with asterisk notation
    expect($result['fields'])->toHaveKey('tags');
    expect($result['fields']['tags']['type'])->toBe('array<int, string>');

    expect($result['fields'])->toHaveKey('preferences');
    expect($result['fields']['preferences']['type'])->toBe('array<int, string>');

    // Nested objects
    expect($result['fields'])->toHaveKey('profile');
    expect($result['fields']['profile']['type'])->toBe('array{first_name: string, last_name: string, age: ?int}');

    expect($result['fields'])->toHaveKey('settings');
    expect($result['fields']['settings']['type'])->toBe('array{theme: string, notifications: bool, language: ?string}');

    // Total of 5 fields (name, tags, preferences, profile, settings)
    expect($result['fields'])->toHaveCount(5);
});



function createTempFormRequest(array $rules): string
{
    $rulesString = '';
    foreach ($rules as $field => $rule) {
        $rulesString .= "            '{$field}' => '{$rule}',\n";
    }

    // Generate unique class name to avoid conflicts between tests
    $uniqueId = uniqid();
    $className = "TestRequest{$uniqueId}";

    $content = "<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {$className} extends FormRequest
{
    public function rules(): array
    {
        return [
{$rulesString}        ];
    }
}";

    $tempFile = tempnam(sys_get_temp_dir(), 'form_request_test');
    file_put_contents($tempFile, $content);

    return $tempFile;
}
