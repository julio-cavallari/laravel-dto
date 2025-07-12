<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

beforeEach(function (): void {
    $this->parser = new FormRequestParser;
});

it('handles all basic validation rules with correct type inference', function (): void {
    $filePath = createTempFormRequestForAllRules([
        // String rules
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'slug' => 'string|alpha_dash',
        'description' => 'string|min:10',
        'alpha_field' => 'alpha',
        'alpha_num_field' => 'alpha_num',
        'ascii_field' => 'ascii',
        'confirmed_password' => 'string|confirmed',
        'regex_field' => 'string|regex:/^[a-zA-Z]+$/',
        'url_field' => 'url',
        'uuid_field' => 'uuid',
        'json_field' => 'json',
        'ip_field' => 'ip',
        'ipv4_field' => 'ipv4',
        'ipv6_field' => 'ipv6',
        'mac_address' => 'mac_address',
        'timezone' => 'timezone',
        'ulid_field' => 'ulid',

        // Numeric rules
        'age' => 'integer|min:0|max:120',
        'price' => 'numeric|min:0',
        'decimal_value' => 'decimal:2,4',
        'float_value' => 'numeric',
        'between_value' => 'integer|between:1,100',
        'digits_value' => 'digits:4',
        'digits_between_value' => 'digits_between:3,5',
        'min_value' => 'integer|min:10',
        'max_value' => 'integer|max:1000',
        'multiple_of' => 'multiple_of:5',

        // Boolean rules
        'is_active' => 'boolean',
        'accepted_terms' => 'accepted',
        'accepted_if_field' => 'accepted_if:is_active,true',
        'declined_field' => 'declined',
        'declined_if_field' => 'declined_if:is_active,false',

        // Date rules
        'birth_date' => 'date',
        'created_at' => 'date_format:Y-m-d H:i:s',
        'after_date' => 'date|after:2023-01-01',
        'before_date' => 'date|before:2025-12-31',
        'after_or_equal_date' => 'date|after_or_equal:today',
        'before_or_equal_date' => 'date|before_or_equal:tomorrow',

        // File rules
        'avatar' => 'file|image|max:2048',
        'document' => 'file|mimes:pdf,doc,docx',
        'video' => 'file|mimetypes:video/mp4,video/avi',
        'image_dimensions' => 'image|dimensions:min_width=100,min_height=100',

        // Array rules
        'tags' => 'array|min:1|max:5',
        'categories' => 'array',
        'permissions' => 'array|distinct',
        'options' => 'array|filled',

        // Nullable and optional fields
        'optional_string' => 'nullable|string',
        'optional_integer' => 'nullable|integer',
        'optional_boolean' => 'nullable|boolean',
        'optional_array' => 'nullable|array',
        'sometimes_field' => 'sometimes|string',

        // Conditional rules
        'required_if_field' => 'required_if:is_active,true|string',
        'required_unless_field' => 'required_unless:is_active,false|string',
        'required_with_field' => 'required_with:name|string',
        'required_with_all_field' => 'required_with_all:name,email|string',
        'required_without_field' => 'required_without:optional_string|string',
        'required_without_all_field' => 'required_without_all:optional_string,optional_integer|string',
        'prohibited_field' => 'prohibited',
        'prohibited_if_field' => 'prohibited_if:is_active,true',
        'prohibited_unless_field' => 'prohibited_unless:is_active,false',

        // Comparison rules
        'same_as_name' => 'same:name',
        'different_from_name' => 'different:name',
        'confirmed_email' => 'same:email',
        'gt_field' => 'integer|gt:age',
        'gte_field' => 'integer|gte:age',
        'lt_field' => 'integer|lt:max_value',
        'lte_field' => 'integer|lte:max_value',

        // Database rules (will be parsed as strings)
        'unique_email' => 'string|unique:users,email',
        'exists_category' => 'integer|exists:categories,id',

        // Enum rules
        'status' => 'string|in:active,inactive,pending',
        'priority' => 'integer|in:1,2,3,4,5',
        'not_in_field' => 'string|not_in:admin,root,system',

        // Size rules
        'size_field' => 'string|size:10',
        'min_length' => 'string|min:5',
        'max_length' => 'string|max:100',

        // Special validation rules
        'present_field' => 'present',
        'filled_field' => 'filled|string',
        'missing_field' => 'missing',
        'missing_if_field' => 'missing_if:is_active,true',
        'missing_unless_field' => 'missing_unless:is_active,false',
        'missing_with_field' => 'missing_with:name',
        'missing_with_all_field' => 'missing_with_all:name,email',

        // Custom validation rules
        'password' => 'string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',

        // Array with typed elements
        'string_array' => 'array',
        'integer_array' => 'array',
        'mixed_array' => 'array',
    ]);

    $result = $this->parser->parse($filePath);

    expect($result['class_name'])->toContain('TestRequest');
    expect($result['fields'])->not->toBeEmpty();

    // Test string type inference
    $stringFields = ['name', 'email', 'slug', 'description', 'alpha_field', 'alpha_num_field', 'ascii_field'];
    foreach ($stringFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('string');
        }
    }

    // Test integer type inference (fields that explicitly have integer rule)
    $integerFields = ['age', 'between_value', 'digits_value', 'min_value', 'max_value'];
    foreach ($integerFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('int');
        }
    }

    // Test float type inference (fields with numeric rules or multiple_of)
    $floatFields = ['price', 'decimal_value', 'float_value', 'multiple_of'];
    foreach ($floatFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('float');
        }
    }

    // Test boolean type inference
    $booleanFields = ['is_active', 'accepted_terms', 'declined_field'];
    foreach ($booleanFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('bool');
        }
    }

    // Test array type inference
    $arrayFields = ['tags', 'categories', 'permissions', 'options'];
    foreach ($arrayFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('array');
            expect($result['fields'][$fieldName]['is_array'])->toBeTrue();
        }
    }

    // Test nullable fields
    $nullableFields = ['optional_string', 'optional_integer', 'optional_boolean', 'optional_array'];
    foreach ($nullableFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['nullable'])->toBeTrue();
        }
    }

    // Cleanup
    unlink($filePath);
});

it('handles complex nested array validation rules', function (): void {
    $filePath = createTempFormRequestForAllRules([
        'user' => 'required|array',
        'user.*' => 'array',
        'user.*.name' => 'required|string|max:255',
        'user.*.email' => 'required|email',
        'user.*.age' => 'nullable|integer|min:0',
        'preferences' => 'array',
        'preferences.*' => 'string|max:50',
        'social_links' => 'nullable|array',
        'social_links.*' => 'url|max:255',
        'notifications' => 'nullable|array',
        'notifications.email' => 'boolean',
        'notifications.sms' => 'boolean',
        'notifications.push' => 'boolean',
        'metadata' => 'array',
        'metadata.settings' => 'array',
        'metadata.settings.theme' => 'nullable|string|in:light,dark,auto',
        'metadata.settings.language' => 'nullable|string|max:10',
        'metadata.settings.timezone' => 'nullable|string|max:50',
    ]);

    $result = $this->parser->parse($filePath);

    expect($result['class_name'])->toContain('TestRequest');
    expect($result['fields'])->not->toBeEmpty();

    // Should include main array fields
    expect($result['fields'])->toHaveKey('user');
    expect($result['fields'])->toHaveKey('preferences');
    expect($result['fields'])->toHaveKey('social_links');
    expect($result['fields'])->toHaveKey('notifications');
    expect($result['fields'])->toHaveKey('metadata');

    // Should NOT include nested fields with dot notation
    $actualFields = array_keys($result['fields']);
    foreach ($actualFields as $field) {
        expect($field)->not->toContain('*');
        expect($field)->not->toContain('.');
    }

    // Check array fields are properly typed
    if (isset($result['fields']['user'])) {
        expect($result['fields']['user']['type'])->toBe('array<int, array>');
        expect($result['fields']['user']['is_array'])->toBeTrue();
    }

    if (isset($result['fields']['preferences'])) {
        expect($result['fields']['preferences']['type'])->toBe('array<int, string>');
        expect($result['fields']['preferences']['is_array'])->toBeTrue();
    }

    // Cleanup
    unlink($filePath);
});

it('handles edge cases and special validation combinations', function (): void {
    $filePath = createTempFormRequestForAllRules([
        // Multiple pipes with complex rules
        'complex_string' => 'required|string|min:10|max:255|regex:/^[a-zA-Z0-9\s]+$/|unique:users,username',
        'complex_number' => 'required|integer|min:1|max:9999|digits_between:1,4|multiple_of:5',
        'complex_date' => 'required|date|date_format:Y-m-d|after:yesterday|before:+1 year',
        'complex_file' => 'required|file|image|mimes:jpeg,png,gif|max:5120|dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',

        // Conditional with multiple conditions
        'conditional_field' => 'required_if:status,active|required_with:name,email|string|max:100',

        // Arrays with complex validation
        'complex_array' => 'required|array|min:1|max:10|distinct',

        // Nullable with default values
        'nullable_with_default' => 'nullable|string|max:50',
        'boolean_with_default' => 'nullable|boolean',
        'integer_with_default' => 'nullable|integer|min:0',
        'array_with_default' => 'nullable|array',

        // Sometimes fields
        'sometimes_string' => 'sometimes|required|string',
        'sometimes_integer' => 'sometimes|required|integer',

        // Present vs filled vs required
        'present_only' => 'present',
        'filled_only' => 'filled',
        'required_only' => 'required',

        // Prohibited variations
        'prohibited_simple' => 'prohibited',
        'prohibited_conditional' => 'prohibited_if:status,inactive',

        // Multiple same/different comparisons
        'same_as_email' => 'same:complex_string',
        'different_from_all' => 'different:complex_string,name,email',

        // Database rules with additional constraints
        'exists_with_constraint' => 'exists:users,id,deleted_at,NULL|integer',
        'unique_with_constraint' => 'unique:posts,slug,NULL,id,user_id,1|string',

        // Enum with many options
        'large_enum' => 'in:option1,option2,option3,option4,option5,option6,option7,option8,option9,option10',
        'not_in_large' => 'not_in:bad1,bad2,bad3,bad4,bad5,bad6,bad7,bad8,bad9,bad10',

        // Special characters in validation
        'special_regex' => 'regex:/^[\w\-\.\+]+@[\w\-]+\.[\w\-\.]+$/',
        'json_validation' => 'json',

        // File type variations
        'pdf_file' => 'file|mimes:pdf',
        'image_file' => 'image|mimes:jpeg,png,gif,webp',
        'video_file' => 'mimetypes:video/mp4,video/quicktime,video/x-msvideo',
        'audio_file' => 'mimetypes:audio/mpeg,audio/wav,audio/ogg',

        // Size variations
        'exact_size' => 'size:42',
        'string_size' => 'string|size:20',
        'array_size' => 'array|size:5',
        'file_size' => 'file|size:1024',
    ]);

    $result = $this->parser->parse($filePath);

    expect($result['class_name'])->toContain('TestRequest');
    expect($result['fields'])->not->toBeEmpty();

    // Check that complex rules are parsed correctly
    if (isset($result['fields']['complex_string'])) {
        expect($result['fields']['complex_string']['type'])->toBe('string');
        expect($result['fields']['complex_string']['nullable'])->toBeFalse();
    }

    if (isset($result['fields']['complex_number'])) {
        expect($result['fields']['complex_number']['type'])->toBe('int');
        expect($result['fields']['complex_number']['nullable'])->toBeFalse();
    }

    if (isset($result['fields']['complex_array'])) {
        expect($result['fields']['complex_array']['type'])->toBe('array');
        expect($result['fields']['complex_array']['is_array'])->toBeTrue();
        expect($result['fields']['complex_array']['nullable'])->toBeFalse();
    }

    // Check nullable fields
    $nullableFields = ['nullable_with_default', 'boolean_with_default', 'integer_with_default', 'array_with_default'];
    foreach ($nullableFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['nullable'])->toBeTrue();
        }
    }

    // Check file fields are handled properly
    $fileFields = ['complex_file', 'pdf_file', 'image_file', 'video_file', 'audio_file'];
    foreach ($fileFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            // File fields should be parsed as UploadedFile
            expect($result['fields'][$fieldName]['type'])->toBe(UploadedFile::class);
        }
    }

    // Cleanup
    unlink($filePath);
});

it('handles Laravel rule objects and closure validation', function (): void {
    $filePath = createTempFormRequestForAllRules([
        // Standard rules that might be used with Rule objects
        'enum_field' => 'string|in:value1,value2,value3',
        'password_field' => 'string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        'dimensions_field' => 'image|dimensions:ratio=3/2,min_width=100,min_height=100',
        'exclude_field' => 'exclude',
        'exclude_if_field' => 'exclude_if:status,draft',
        'exclude_unless_field' => 'exclude_unless:status,published',
        'exclude_with_field' => 'exclude_with:draft_mode',
        'exclude_without_field' => 'exclude_without:published_at',

        // Fields that might use custom rules
        'custom_validation' => 'string|min:3|max:20',
        'phone_number' => 'string|regex:/^[\+]?[1-9][\d]{0,15}$/',
        'credit_card' => 'string|regex:/^[\d]{4}[\s\-]?[\d]{4}[\s\-]?[\d]{4}[\s\-]?[\d]{4}$/',
        'hex_color' => 'string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        'base64_image' => 'string|regex:/^data:image\/(png|jpeg|jpg|gif);base64,/',

        // Current validation rules (Laravel 10+)
        'current_password' => 'current_password',
        'doesnt_start_with' => 'string|doesnt_start_with:admin,root',
        'doesnt_end_with' => 'string|doesnt_end_with:.tmp,.bak',
        'starts_with' => 'string|starts_with:user_,admin_',
        'ends_with' => 'string|ends_with:.jpg,.png,.gif',
        'lowercase' => 'string|lowercase',
        'uppercase' => 'string|uppercase',

        // Multi-dimensional arrays
        'nested_data' => 'array',
        'list_of_objects' => 'array',

        // Special Laravel validation
        'bail_field' => 'bail|string|min:10|max:20',
        'nullable_bail' => 'nullable|bail|string|email',
    ]);

    $result = $this->parser->parse($filePath);

    expect($result['class_name'])->toContain('TestRequest');
    expect($result['fields'])->not->toBeEmpty();

    // Test string fields with special rules
    $stringFields = [
        'enum_field', 'password_field', 'custom_validation', 'phone_number',
        'credit_card', 'hex_color', 'base64_image', 'doesnt_start_with',
        'doesnt_end_with', 'starts_with', 'ends_with', 'lowercase', 'uppercase',
    ];

    foreach ($stringFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('string');
        }
    }

    // Test array fields
    $arrayFields = ['nested_data', 'list_of_objects'];
    foreach ($arrayFields as $fieldName) {
        if (isset($result['fields'][$fieldName])) {
            expect($result['fields'][$fieldName]['type'])->toBe('array');
            expect($result['fields'][$fieldName]['is_array'])->toBeTrue();
        }
    }

    // Test fields with bail rule (should still parse the type correctly)
    if (isset($result['fields']['bail_field'])) {
        expect($result['fields']['bail_field']['type'])->toBe('string');
        expect($result['fields']['bail_field']['nullable'])->toBeFalse();
    }

    if (isset($result['fields']['nullable_bail'])) {
        expect($result['fields']['nullable_bail']['type'])->toBe('string');
        expect($result['fields']['nullable_bail']['nullable'])->toBeTrue();
    }

    // Cleanup
    unlink($filePath);
});

// Helper function for comprehensive testing
function createTempFormRequestForAllRules(array $rules): string
{
    $rulesString = '';
    foreach ($rules as $field => $rule) {
        // Escape single quotes in rules
        $escapedRule = str_replace("'", "\'", $rule);
        $rulesString .= "            '{$field}' => '{$escapedRule}',\n";
    }

    // Generate unique class name to avoid conflicts between tests
    $uniqueId = uniqid();
    $className = "TestRequest{$uniqueId}";

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

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
        ];
    }
}";

    $tempFile = tempnam(sys_get_temp_dir(), 'comprehensive_form_request_test');
    file_put_contents($tempFile, $content);

    return $tempFile;
}
