<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Parsers;

use JulioCavallari\LaravelDto\Attributes\UseDto;
use ReflectionClass;
use ReflectionMethod;

/**
 * Form Request Parser
 *
 * Parses Form Request classes to extract validation rules and field information.
 */
class FormRequestParser
{
    /**
     * Parse a Form Request file to extract field information.
     *
     * @param  string  $filePath  Path to the Form Request file
     * @return array{class_name: string, namespace: string, short_name: string, form_request_class: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, custom_dto_class: string|null} Parsed data containing class info and fields
     */
    public function parse(string $filePath): array
    {
        $className = $this->getFullyQualifiedClassName($filePath);

        if (! class_exists($className)) {
            require_once $filePath;
        }

        $reflection = new ReflectionClass($className);
        $rulesMethod = $this->getRulesMethod($reflection);

        if (! $rulesMethod instanceof \ReflectionMethod) {
            throw new \Exception("No rules() method found in {$className}");
        }

        $rules = $this->extractRules($reflection, $rulesMethod);
        $fields = $this->parseFields($rules);

        // Check for UseDto attribute to get custom DTO class name
        $customDtoClass = $this->getCustomDtoClass($reflection);

        return [
            'class_name' => $reflection->getShortName(),
            'namespace' => $reflection->getNamespaceName(),
            'short_name' => $reflection->getShortName(),
            'form_request_class' => $className,
            'fields' => $fields,
            'custom_dto_class' => $customDtoClass,
        ];
    }

    /**
     * Get the fully qualified class name from file path.
     */
    private function getFullyQualifiedClassName(string $filePath): string
    {
        $content = file_get_contents($filePath);

        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? '';

        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? '';

        if (! $namespace || ! $className) {
            throw new \Exception("Could not extract class information from {$filePath}");
        }

        return $namespace.'\\'.$className;
    }

    /**
     * Get the rules method from reflection.
     *
     * @param  ReflectionClass<object>  $reflection
     */
    private function getRulesMethod(ReflectionClass $reflection): ?ReflectionMethod
    {
        try {
            return $reflection->getMethod('rules');
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Extract validation rules from the Form Request.
     *
     * @param  ReflectionClass<object>  $reflection
     * @return array<string, mixed>
     */
    private function extractRules(ReflectionClass $reflection, ReflectionMethod $rulesMethod): array
    {
        try {
            $instance = $reflection->newInstanceWithoutConstructor();
            $rules = $rulesMethod->invoke($instance);

            return is_array($rules) ? $rules : [];
        } catch (\Exception) {
            // If we can't instantiate, try to parse the method statically
            return $this->parseRulesFromSource($reflection, $rulesMethod);
        }
    }

    /**
     * Parse rules from source code when instantiation fails.
     *
     * @param  ReflectionClass<object>  $reflection
     * @return array<string, mixed>
     */
    private function parseRulesFromSource(ReflectionClass $reflection, ReflectionMethod $rulesMethod): array
    {
        $fileName = $reflection->getFileName();
        $startLine = $rulesMethod->getStartLine();
        $endLine = $rulesMethod->getEndLine();

        $lines = file($fileName);
        $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Extract the return array more robustly
        if (preg_match('/return\s*\[(.*?)\];/s', $methodCode, $matches)) {
            $rulesString = $matches[1];
            return $this->parseRulesString($rulesString);
        }

        return [];
    }

    /**
     * Parse rules string to extract field rules.
     *
     * @return array<string, string>
     */
    private function parseRulesString(string $rulesString): array
    {
        $rules = [];

        $pattern = '/[\'"]([^\'\"]+)[\'"]\s*=>\s*[\'"]([^\'\"]+)[\'"]/';
        if (preg_match_all($pattern, $rulesString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rules[$match[1]] = $match[2];
            }
        }

        return $rules;
    }

    /**
     * Parse fields from validation rules.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>
     */
    private function parseFields(array $rules): array
    {
        $fields = [];
        $typeMapping = $this->getTypeMapping();

        // First, process array fields with asterisk notation
        $arrayFields = $this->processArrayFields($rules, $typeMapping);

        // Then process nested fields
        $nestedFields = $this->processNestedFields($rules, $typeMapping);

        // Finally, process regular fields
        foreach ($rules as $fieldName => $rule) {
            // Skip nested field rules - they are handled separately
            if ($this->isNestedFieldRule($fieldName)) {
                continue;
            }

            // Skip fields that are already processed as array or nested fields
            if (isset($arrayFields[$fieldName]) || isset($nestedFields[$fieldName])) {
                continue;
            }

            $fieldInfo = $this->parseFieldInfo($fieldName, $rule, $typeMapping);
            $fields[$fieldName] = [
                'type' => $fieldInfo['type'],
                'nullable' => $fieldInfo['nullable'],
                'default' => $fieldInfo['has_default'] ? $fieldInfo['default_value'] : null,
                'name' => $fieldInfo['name'],
                'is_array' => $fieldInfo['is_array'],
                'has_default' => $fieldInfo['has_default'],
                'default_value' => $fieldInfo['default_value'],
                'rules' => $fieldInfo['rules'],
            ];
        }

        // Merge array fields and nested fields with regular fields
        return array_merge($fields, $arrayFields, $nestedFields);
    }

    /**
     * Process array fields with asterisk notation (e.g., 'preferences.*').
     * These become array<int, type> in the DTO.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $typeMapping
     * @return array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>
     */
    private function processArrayFields(array $rules, array $typeMapping): array
    {
        $arrayFields = [];

        foreach ($rules as $fieldName => $rule) {
            if (str_contains($fieldName, '*')) {
                // This is an array field like 'tags.*' or 'preferences.*.name'
                $baseFieldName = str_replace('.*', '', $fieldName);

                // Skip complex nested array fields (like 'preferences.*.name')
                if (str_contains($baseFieldName, '.')) {
                    continue;
                }

                $fieldRules = is_string($rule) ? explode('|', $rule) : (array) $rule;

                // Infer the type of array elements
                $elementType = $this->inferType($fieldRules, $typeMapping);
                $isNullable = in_array('nullable', $fieldRules);

                $arrayFields[$baseFieldName] = [
                    'type' => "array<int, {$elementType}>",
                    'nullable' => $isNullable,
                    'default' => $isNullable ? null : [],
                    'name' => $baseFieldName,
                    'is_array' => true,
                    'has_default' => true,
                    'default_value' => $isNullable ? null : [],
                    'rules' => $fieldRules,
                ];
            }
        }

        return $arrayFields;
    }

    /**
     * Process nested fields with dot notation (e.g., 'notifications.email').
     * These are grouped and become complex types in the DTO.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $typeMapping
     * @return array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>
     */
    private function processNestedFields(array $rules, array $typeMapping): array
    {
        $nestedGroups = [];
        $processedFields = [];

        // Group nested fields by their base name
        foreach ($rules as $fieldName => $rule) {
            if (str_contains($fieldName, '.') && !str_contains($fieldName, '*')) {
                $parts = explode('.', $fieldName);
                $baseName = $parts[0];
                $nestedName = implode('.', array_slice($parts, 1));

                if (!isset($nestedGroups[$baseName])) {
                    $nestedGroups[$baseName] = [];
                }

                $fieldRules = is_string($rule) ? explode('|', $rule) : (array) $rule;
                $fieldType = $this->inferType($fieldRules, $typeMapping);
                $isNullable = in_array('nullable', $fieldRules);

                $nestedGroups[$baseName][$nestedName] = [
                    'type' => $fieldType,
                    'nullable' => $isNullable,
                    'rules' => $fieldRules,
                ];
            }
        }

        foreach ($nestedGroups as $baseName => $nestedFields) {
            $nestedTypeDefinition = $this->buildNestedTypeDefinition($nestedFields);

            $processedFields[$baseName] = [
                'type' => "array{" . $nestedTypeDefinition . "}",
                'nullable' => false, 
                'default' => [],
                'name' => $baseName,
                'is_array' => true,
                'has_default' => true,
                'default_value' => [],
                'rules' => ['array'], 
            ];
        }

        return $processedFields;
    }

    /**
     * Build type definition for nested fields.
     *
     * @param  array<string, array{type: string, nullable: bool, rules: array<string>}>  $nestedFields
     */
    private function buildNestedTypeDefinition(array $nestedFields): string
    {
        $typeDefinitions = [];

        foreach ($nestedFields as $fieldName => $fieldInfo) {
            $type = $fieldInfo['type'];
            if ($fieldInfo['nullable']) {
                $type = "?{$type}";
            }
            $typeDefinitions[] = "{$fieldName}: {$type}";
        }

        return implode(', ', $typeDefinitions);
    }

    /**
     * Check if a field name represents a nested field rule.
     *
     * @param string $fieldName
     * @return bool
     */
    private function isNestedFieldRule(string $fieldName): bool
    {
        if (str_contains($fieldName, '*')) {
            return true;
        }

        if (str_contains($fieldName, '.')) {
            return true;
        }

        return false;
    }

    /**
     * Parse individual field information.
     *
     * @param  array<string, string>  $typeMapping
     * @return array{name: string, type: string, nullable: bool, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}
     */
    private function parseFieldInfo(string $fieldName, mixed $rule, array $typeMapping): array
    {
        $rules = is_string($rule) ? explode('|', $rule) : (array) $rule;

        $isNullable = in_array('nullable', $rules);
        $isArray = in_array('array', $rules);
        $type = $this->inferType($rules, $typeMapping);
        $hasDefault = $this->hasDefaultValue($rules);
        $defaultValue = $this->getDefaultValue($rules, $type);

        return [
            'name' => $fieldName,
            'type' => $type,
            'nullable' => $isNullable,
            'is_array' => $isArray,
            'has_default' => $hasDefault,
            'default_value' => $defaultValue,
            'rules' => $rules,
        ];
    }

    /**
     * Infer PHP type from validation rules.
     *
     * @param  array<string>  $rules
     * @param  array<string, string>  $typeMapping
     */
    private function inferType(array $rules, array $typeMapping): string
    {
        foreach ($rules as $rule) {
            $ruleName = explode(':', $rule)[0];

            if (isset($typeMapping[$ruleName])) {
                return $typeMapping[$ruleName];
            }
        }

        if (in_array('array', $rules)) {
            return 'array';
        }

        if (in_array('integer', $rules) || in_array('int', $rules)) {
            return 'int';
        }

        if (in_array('numeric', $rules)) {
            return 'float';
        }

        if (in_array('boolean', $rules) || in_array('bool', $rules) || in_array('accepted', $rules)) {
            return 'bool';
        }

        return 'string';
    }

    /**
     * Check if field has a default value.
     *
     * @param  array<string>  $rules
     */
    private function hasDefaultValue(array $rules): bool
    {
        return in_array('nullable', $rules) || $this->hasExplicitDefault($rules);
    }

    /**
     * Check if field has an explicit default value in rules.
     *
     * @param  array<string>  $rules
     */
    private function hasExplicitDefault(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'default:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default value for field.
     *
     * @param  array<string>  $rules
     */
    private function getDefaultValue(array $rules, string $type): mixed
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'default:')) {
                $value = substr($rule, 8);

                return $this->castDefaultValue($value, $type);
            }
        }

        if (in_array('nullable', $rules)) {
            return null;
        }

        return match ($type) {
            'array' => [],
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            default => null,
        };
    }

    /**
     * Cast default value to appropriate type.
     */
    private function castDefaultValue(string $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => json_decode($value, true) ?: [],
            default => $value,
        };
    }

    /**
     * Get custom DTO class from UseDto attribute if present.
     *
     * @param  ReflectionClass<object>  $reflection
     */
    private function getCustomDtoClass(ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(UseDto::class);

        if ($attributes === []) {
            return null;
        }

        try {
            $attribute = $attributes[0]->newInstance();

            return $attribute->dtoClass;
        } catch (\Exception) {
            return null;
        }
    }    
    
    /**
     * Get type mapping configuration.
     *
     * @return array<string, string>
     */
    private function getTypeMapping(): array
    {
        return config('laravel-dto.type_mapping', [
            'accepted' => 'bool',
            'accepted_if' => 'bool',
            'boolean' => 'bool',
            'declined' => 'bool',
            'declined_if' => 'bool',
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
            'array' => 'array',
            'contains' => 'array',
            'distinct' => 'array',
            'in' => 'string',
            'in_array' => 'string',
            'list' => 'array',
            'not_in' => 'string',
            'required_array_keys' => 'array',
            'after' => 'Illuminate\\Support\\Carbon',
            'after_or_equal' => 'Illuminate\\Support\\Carbon',
            'before' => 'Illuminate\\Support\\Carbon',
            'before_or_equal' => 'Illuminate\\Support\\Carbon',
            'date' => 'Illuminate\\Support\\Carbon',
            'date_equals' => 'Illuminate\\Support\\Carbon',
            'date_format' => 'Illu',
            'timezone' => 'string',
            'dimensions' => 'Illuminate\\Http\\UploadedFile',
            'extensions' => 'Illuminate\\Http\\UploadedFile',
            'file' => 'Illuminate\\Http\\UploadedFile',
            'image' => 'Illuminate\\Http\\UploadedFile',
            'mimes' => 'Illuminate\\Http\\UploadedFile',
            'mimetypes' => 'Illuminate\\Http\\UploadedFile',
            'exists' => 'mixed',
            'unique' => 'mixed',
            'ip' => 'string',
            'ipv4' => 'string',
            'ipv6' => 'string',
            'json' => 'array',
            'enum' => 'mixed',
            'bool' => 'bool',
            'int' => 'int',
            'float' => 'float',
        ]);
    }
}
