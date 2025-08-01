<?php

declare(strict_types=1);

use JulioCavallari\LaravelDto\Parsers\FormRequestParser;
use JulioCavallari\LaravelDto\Generators\DtoGenerator;

beforeEach(function (): void {
    $this->parser = new FormRequestParser();
    $this->generator = new DtoGenerator();
});

it('integrates enum generation with DTO generation', function (): void {
    $filePath = createTempFormRequestForIntegrationTest([
        'title' => 'required|string|max:255',
        'status' => 'required|string|in:draft,published,archived',
        'priority' => 'nullable|integer|in:1,2,3',
        'content' => 'required|string|min:10',
    ]);

    $parsedData = $this->parser->parse($filePath);
    $dtoCode = $this->generator->generate($parsedData);

    // Check that enums were created
    expect($parsedData['enums'])->toHaveCount(2);
    expect($parsedData['enums'])->toHaveKey('StatusEnum');
    expect($parsedData['enums'])->toHaveKey('PriorityEnum');

    // Check DTO code includes enum types
    expect($dtoCode)->toContain('\\App\\Enums\\StatusEnum $status');
    expect($dtoCode)->toContain('?\\App\\Enums\\PriorityEnum $priority');

    // Check DTO code includes enum conversion logic
    expect($dtoCode)->toContain('\\App\\Enums\\StatusEnum::from($status)');
    expect($dtoCode)->toContain('\\App\\Enums\\PriorityEnum::from($priority)');

    // Check that regular string fields remain unchanged
    expect($dtoCode)->toContain('string $title');
    expect($dtoCode)->toContain('string $content');

    // Cleanup
    unlink($filePath);
});

it('generates enum files through DTO generator', function (): void {
    $filePath = createTempFormRequestForIntegrationTest([
        'status' => 'required|string|in:active,inactive',
        'type' => 'string|in:public,private',
    ]);

    $parsedData = $this->parser->parse($filePath);
    $enumFiles = $this->generator->generateEnums($parsedData['enums']);

    expect($enumFiles)->toHaveCount(2);
    
    $statusEnumPath = $this->generator->getEnumPath('StatusEnum');
    $typeEnumPath = $this->generator->getEnumPath('TypeEnum');
    
    expect($enumFiles)->toHaveKey($statusEnumPath);
    expect($enumFiles)->toHaveKey($typeEnumPath);

    // Check enum file content
    $statusEnumCode = $enumFiles[$statusEnumPath];
    expect($statusEnumCode)->toContain('enum StatusEnum: string');
    expect($statusEnumCode)->toContain("case ACTIVE = 'active';");
    expect($statusEnumCode)->toContain("case INACTIVE = 'inactive';");

    $typeEnumCode = $enumFiles[$typeEnumPath];
    expect($typeEnumCode)->toContain('enum TypeEnum: string');
    expect($typeEnumCode)->toContain("case PUBLIC = 'public';");
    expect($typeEnumCode)->toContain("case PRIVATE = 'private';");

    // Cleanup
    unlink($filePath);
});

it('handles mixed enum and regular fields correctly', function (): void {
    $filePath = createTempFormRequestForIntegrationTest([
        'name' => 'required|string|max:255',
        'email' => 'required|email',
        'role' => 'required|string|in:admin,user,guest',
        'age' => 'nullable|integer|min:18',
        'preferences' => 'nullable|array',
        'preferences.*' => 'string',
        'settings' => 'array',
        'settings.theme' => 'string|in:light,dark',
        'settings.language' => 'string|max:10',
    ]);

    $parsedData = $this->parser->parse($filePath);
    $dtoCode = $this->generator->generate($parsedData);

    // Should create enums for 'role' and 'settings.theme'
    expect($parsedData['enums'])->toHaveCount(2);
    expect($parsedData['enums'])->toHaveKey('RoleEnum');
    expect($parsedData['enums'])->toHaveKey('ThemeEnum');

    // Check field types
    expect($parsedData['fields']['name']['type'])->toBe('string');
    expect($parsedData['fields']['email']['type'])->toBe('string');
    expect($parsedData['fields']['role']['type'])->toBe('App\\Enums\\RoleEnum');
    expect($parsedData['fields']['age']['type'])->toBe('int');
    expect($parsedData['fields']['preferences']['type'])->toBe('array<int, string>');

    // Check DTO code
    expect($dtoCode)->toContain('string $name');
    expect($dtoCode)->toContain('string $email');
    expect($dtoCode)->toContain('\\App\\Enums\\RoleEnum $role');
    expect($dtoCode)->toContain('?int $age');

    // Cleanup
    unlink($filePath);
});

// Helper function for integration testing
function createTempFormRequestForIntegrationTest(array $rules): string
{
    $rulesString = '';
    foreach ($rules as $field => $rule) {
        $escapedRule = str_replace("'", "\'", $rule);
        $rulesString .= "            '{$field}' => '{$escapedRule}',\n";
    }

    $uniqueId = uniqid();
    $className = "IntegrationTestRequest{$uniqueId}";

    $content = "<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {$className} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$rulesString}        ];
    }
}";

    $tempFile = tempnam(sys_get_temp_dir(), 'integration_test_form_request');
    file_put_contents($tempFile, $content);

    return $tempFile;
}
