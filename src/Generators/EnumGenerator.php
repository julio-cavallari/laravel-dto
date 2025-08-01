<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Generators;

/**
 * Enum Generator
 *
 * Generates PHP enum classes from validation rule data.
 */
class EnumGenerator
{
    /**
     * Generate an enum class from "in" rule values.
     *
     * @param  string  $enumName  The name of the enum class
     * @param  array<string>  $values  The enum values from the "in" rule
     * @param  string  $namespace  The namespace for the enum
     * @return string  The generated enum class code
     */
    public function generateEnum(string $enumName, array $values, string $namespace = 'App\\Enums'): string
    {
        $enumType = $this->determineEnumType($values);
        $cases = $this->generateEnumCases($values, $enumType);

        $template = "<?php

declare(strict_types=1);

namespace {$namespace};

enum {$enumName}: {$enumType}
{
{$cases}
}
";

        return $template;
    }    /**
     * Determine the backing type for the enum based on values.
     *
     * @param  array<string>  $values
     */
    private function determineEnumType(array $values): string
    {
        $hasNonNumeric = false;
        $hasNonInteger = false;

        foreach ($values as $value) {
            if (!is_numeric($value)) {
                $hasNonNumeric = true;
                break;
            }

            if (is_numeric($value) && !ctype_digit($value) && $value !== (string)(int)$value) {
                $hasNonInteger = true;
            }
        }

        if ($hasNonNumeric) {
            return 'string';
        }

        if ($hasNonInteger) {
            return 'float';
        }

        return 'int';
    }

    /**
     * Generate enum cases from values.
     *
     * @param  array<string>  $values
     */
    private function generateEnumCases(array $values, string $enumType): string
    {
        $cases = [];

        foreach ($values as $value) {
            $caseName = $this->generateCaseName($value);
            $caseValue = $this->formatValue($value, $enumType);
            $cases[] = "    case {$caseName} = {$caseValue};";
        }

        return implode("\n", $cases);
    }

    /**
     * Generate a valid PHP constant name from an enum value.
     */
    private function generateCaseName(string $value): string
    {
        // Remove special characters and convert to uppercase
        $caseName = strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', $value));

        // Remove consecutive underscores first
        $caseName = preg_replace('/_+/', '_', $caseName);

        // Trim trailing underscores only
        $caseName = rtrim($caseName, '_');

        // Ensure it starts with a letter or underscore
        if (is_numeric($caseName[0])) {
            $caseName = '_' . $caseName;
        }

        // If empty or only underscores, use a fallback
        if (empty($caseName) || preg_match('/^_*$/', $caseName)) {
            $caseName = 'VALUE_' . md5($value);
        }

        return $caseName;
    }

    /**
     * Format a value for use in enum case definition.
     */
    private function formatValue(string $value, string $enumType): string
    {
        return match ($enumType) {
            'string' => "'" . addslashes($value) . "'",
            'int' => (string)(int)$value,
            'float' => (string)(float)$value,
            default => "'" . addslashes($value) . "'",
        };
    }

    /**
     * Generate enum class name from field name.
     */
    public function generateEnumClassName(string $fieldName): string
    {
        // Convert snake_case to PascalCase
        $className = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $fieldName)));

        // Only add "Enum" suffix if it doesn't already end with it
        if (!str_ends_with($className, 'Enum')) {
            $className .= 'Enum';
        }

        return $className;
    }

    /**
     * Extract values from an "in" validation rule.
     *
     * @param  string  $inRule  The "in" rule (e.g., "in:active,inactive,pending")
     * @return array<string>  The extracted values
     */
    public function extractInRuleValues(string $inRule): array
    {
        if (!str_starts_with($inRule, 'in:')) {
            return [];
        }

        $valuesString = substr($inRule, 3);
        $values = explode(',', $valuesString);

        return array_map('trim', $values);
    }

    /**
     * Determine if a field should have an enum generated.
     *
     * @param  array<string>  $rules  All validation rules for the field
     * @param  array<string>  $inValues  Values from the "in" rule
     */
    public function shouldGenerateEnum(array $rules, array $inValues): bool
    {
        // Don't generate enum if there are too few values
        if (count($inValues) < 2) {
            return false;
        }

        // Don't generate enum if there are too many values (likely not an enum)
        if (count($inValues) > 20) {
            return false;
        }

        // Don't generate enum if values look like they might be dynamic IDs
        $allNumeric = true;
        foreach ($inValues as $value) {
            if (!is_numeric($value)) {
                $allNumeric = false;
                break;
            }
        }

        // If all values are large numbers, probably IDs not enum values
        if ($allNumeric) {
            foreach ($inValues as $value) {
                if ((int)$value > 100) {
                    return false;
                }
            }
        }

        return true;
    }
}
