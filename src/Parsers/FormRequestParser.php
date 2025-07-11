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

        // Simple regex to extract array rules - this is a basic implementation
        preg_match('/return\s*\[(.*?)\]/s', $methodCode, $matches);

        if ($matches === []) {
            return [];
        }

        // This is a simplified parser - in production, you'd want a more robust solution
        $rulesString = $matches[1];
        $rules = [];

        // Parse simple key => value pairs
        preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $rulesString, $ruleMatches, PREG_SET_ORDER);

        foreach ($ruleMatches as $match) {
            $rules[$match[1]] = $match[2];
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
        $typeMapping = config('laravel-dto.type_mapping', []);

        foreach ($rules as $fieldName => $rule) {
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

        return $fields;
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
        // Check for explicit type rules
        foreach ($rules as $rule) {
            $ruleName = explode(':', $rule)[0];

            if (isset($typeMapping[$ruleName])) {
                return $typeMapping[$ruleName];
            }
        }

        // Check for common patterns
        if (in_array('array', $rules)) {
            return 'array';
        }

        if (in_array('integer', $rules) || in_array('int', $rules)) {
            return 'int';
        }

        if (in_array('numeric', $rules)) {
            return 'float';
        }

        if (in_array('boolean', $rules) || in_array('bool', $rules)) {
            return 'bool';
        }

        // Default to string
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
        // Check for explicit default in rules
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'default:')) {
                $value = substr($rule, 8);

                return $this->castDefaultValue($value, $type);
            }
        }

        // Return appropriate null/empty values
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
}
