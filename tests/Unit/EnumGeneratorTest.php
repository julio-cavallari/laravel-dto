<?php

declare(strict_types=1);

use JulioCavallari\LaravelDto\Generators\EnumGenerator;
use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

beforeEach(function (): void {
    $this->parser = new FormRequestParser();
    $this->enumGenerator = new EnumGenerator();
});

it('generates enums for "in" validation rules', function (): void {
    $filePath = createTempFormRequestForEnumTest([
        'status' => 'required|string|in:active,inactive,pending',
        'priority' => 'required|integer|in:1,2,3',
        'role' => 'nullable|string|in:admin,user,guest',
        'category' => 'string|in:news,sports,tech,entertainment',
    ]);

    $result = $this->parser->parse($filePath);

    // Check that enums were created
    expect($result)->toHaveKey('enums');
    expect($result['enums'])->toHaveCount(4);

    // Check status enum
    expect($result['enums'])->toHaveKey('StatusEnum');
    expect($result['enums']['StatusEnum']['values'])->toBe(['active', 'inactive', 'pending']);
    expect($result['enums']['StatusEnum']['namespace'])->toBe('App\\Enums');

    // Check priority enum
    expect($result['enums'])->toHaveKey('PriorityEnum');
    expect($result['enums']['PriorityEnum']['values'])->toBe(['1', '2', '3']);

    // Check role enum
    expect($result['enums'])->toHaveKey('RoleEnum');
    expect($result['enums']['RoleEnum']['values'])->toBe(['admin', 'user', 'guest']);

    // Check category enum
    expect($result['enums'])->toHaveKey('CategoryEnum');
    expect($result['enums']['CategoryEnum']['values'])->toBe(['news', 'sports', 'tech', 'entertainment']);

    // Check that fields reference the enum types
    expect($result['fields']['status']['type'])->toBe('App\\Enums\\StatusEnum');
    expect($result['fields']['status']['enum_class'])->toBe('App\\Enums\\StatusEnum');
    expect($result['fields']['status']['enum_values'])->toBe(['active', 'inactive', 'pending']);

    expect($result['fields']['priority']['type'])->toBe('App\\Enums\\PriorityEnum');
    expect($result['fields']['role']['type'])->toBe('App\\Enums\\RoleEnum');
    expect($result['fields']['category']['type'])->toBe('App\\Enums\\CategoryEnum');

    // Cleanup
    unlink($filePath);
});

it('does not generate enums for rules with too few values', function (): void {
    $filePath = createTempFormRequestForEnumTest([
        'single_value' => 'string|in:only_one',
        'normal_field' => 'string|max:255',
    ]);

    $result = $this->parser->parse($filePath);

    // Should not create enums for single values
    expect($result['enums'])->toBeEmpty();
    expect($result['fields']['single_value']['type'])->toBe('string');
    expect($result['fields']['normal_field']['type'])->toBe('string');

    // Cleanup
    unlink($filePath);
});

it('does not generate enums for rules with too many values', function (): void {
    $largeValuesList = implode(',', array_map(fn ($i) => "value{$i}", range(1, 25)));

    $filePath = createTempFormRequestForEnumTest([
        'large_list' => "string|in:{$largeValuesList}",
        'normal_field' => 'string|max:255',
    ]);

    $result = $this->parser->parse($filePath);

    // Should not create enums for too many values
    expect($result['enums'])->toBeEmpty();
    expect($result['fields']['large_list']['type'])->toBe('string');

    // Cleanup
    unlink($filePath);
});

it('does not generate enums for large numeric IDs', function (): void {
    $filePath = createTempFormRequestForEnumTest([
        'user_ids' => 'integer|in:1001,1002,1003,1004',
        'status' => 'string|in:active,inactive', // This should still create an enum
    ]);

    $result = $this->parser->parse($filePath);

    // Should not create enum for large numeric IDs, but should for status
    expect($result['enums'])->toHaveCount(1);
    expect($result['enums'])->toHaveKey('StatusEnum');
    expect($result['fields']['user_ids']['type'])->toBe('int');
    expect($result['fields']['status']['type'])->toBe('App\\Enums\\StatusEnum');

    // Cleanup
    unlink($filePath);
});

it('generates correct enum class names', function (): void {
    $cases = [
        'user_status' => 'UserStatusEnum',
        'payment_method' => 'PaymentMethodEnum',
        'content_type' => 'ContentTypeEnum',
        'simple' => 'SimpleEnum',
        'already_enum' => 'AlreadyEnum', // Should not double the Enum suffix
    ];

    foreach ($cases as $fieldName => $expectedClassName) {
        $enumClassName = $this->enumGenerator->generateEnumClassName($fieldName);
        expect($enumClassName)->toBe($expectedClassName);
    }
});

it('extracts values from in rules correctly', function (): void {
    $cases = [
        'in:active,inactive,pending' => ['active', 'inactive', 'pending'],
        'in:1,2,3,4,5' => ['1', '2', '3', '4', '5'],
        'in:admin,user' => ['admin', 'user'],
        'in:value_with_underscore,another-value' => ['value_with_underscore', 'another-value'],
    ];

    foreach ($cases as $rule => $expectedValues) {
        $values = $this->enumGenerator->extractInRuleValues($rule);
        expect($values)->toBe($expectedValues);
    }
});

it('generates proper enum code', function (): void {
    $enumCode = $this->enumGenerator->generateEnum(
        'StatusEnum',
        ['active', 'inactive', 'pending'],
        'App\\Enums'
    );

    expect($enumCode)->toContain('enum StatusEnum: string');
    expect($enumCode)->toContain("case ACTIVE = 'active';");
    expect($enumCode)->toContain("case INACTIVE = 'inactive';");
    expect($enumCode)->toContain("case PENDING = 'pending';");
    expect($enumCode)->not->toContain('public static function values()');
    expect($enumCode)->not->toContain('public static function tryFrom(');
    expect($enumCode)->not->toContain('public static function isValid(');
});

it('generates integer enum when all values are integers', function (): void {
    $enumCode = $this->enumGenerator->generateEnum(
        'PriorityEnum',
        ['1', '2', '3'],
        'App\\Enums'
    );

    expect($enumCode)->toContain('enum PriorityEnum: int');
    expect($enumCode)->toContain('case _1 = 1;');
    expect($enumCode)->toContain('case _2 = 2;');
    expect($enumCode)->toContain('case _3 = 3;');
});

it('generates string enum when values are mixed', function (): void {
    $enumCode = $this->enumGenerator->generateEnum(
        'MixedEnum',
        ['1', 'active', '3'],
        'App\\Enums'
    );

    expect($enumCode)->toContain('enum MixedEnum: string');
    expect($enumCode)->toContain("case _1 = '1';");
    expect($enumCode)->toContain("case ACTIVE = 'active';");
    expect($enumCode)->toContain("case _3 = '3';");
});

// Helper function for enum testing
function createTempFormRequestForEnumTest(array $rules): string
{
    $rulesString = '';
    foreach ($rules as $field => $rule) {
        $escapedRule = str_replace("'", "\'", $rule);
        $rulesString .= "            '{$field}' => '{$escapedRule}',\n";
    }

    $uniqueId = uniqid();
    $className = "EnumTestRequest{$uniqueId}";

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

    $tempFile = tempnam(sys_get_temp_dir(), 'enum_test_form_request');
    file_put_contents($tempFile, $content);

    return $tempFile;
}
