<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Generators;

use JulioCavallari\LaravelDto\Templates\DtoTemplate;

/**
 * DTO Generator
 *
 * Generates DTO class code from parsed Form Request data.
 */
class DtoGenerator
{
    private readonly DtoTemplate $template;

    public function __construct()
    {
        $this->template = new DtoTemplate();
    }

    /**
     * Generate DTO class code from parsed data.
     *
     * @param  array{class_name: string, namespace: string, short_name: string, form_request_class: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, custom_dto_class: string|null}  $parsedData  Parsed Form Request data
     * @return string Generated PHP code
     */
    public function generate(array $parsedData): string
    {
        $dtoClassName = $this->resolveDtoClassName($parsedData);
        $dtoNamespace = $this->getDtoNamespace();
        $formRequestClass = $parsedData['class_name'];
        $formRequestNamespace = $parsedData['namespace'];

        $constructorParams = $this->generateConstructorParameters($parsedData['fields']);
        $fromRequestMethod = $this->generateFromRequestMethod($parsedData['fields'], $formRequestClass);

        return $this->template->render([
            'namespace' => $dtoNamespace,
            'class_name' => $dtoClassName,
            'fields' => $parsedData['fields'],
            'form_request_class' => $formRequestClass,
            'form_request_namespace' => $formRequestNamespace,
            'constructor_params' => $constructorParams,
            'from_request_method' => $fromRequestMethod,
            'readonly' => $this->isReadonly(),
            'generate_from_request' => $this->shouldGenerateFromRequest(),
            'use_imports' => true,
        ]);
    }

    /**
     * Resolve DTO class name from parsed data.
     *
     * @param  array{class_name: string, namespace: string, short_name: string, form_request_class: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, custom_dto_class: string|null}  $parsedData
     */
    private function resolveDtoClassName(array $parsedData): string
    {
        if (! empty($parsedData['custom_dto_class'])) {
            return class_basename($parsedData['custom_dto_class']);
        }

        return $this->generateDtoClassName($parsedData['class_name']);
    }

    /**
     * Get the file path where DTO should be saved.
     */
    public function getDtoPath(string $formRequestClassName, ?string $customDtoClass = null): string
    {
        $dtoClassName = $customDtoClass ? class_basename($customDtoClass) : $this->generateDtoClassName($formRequestClassName);

        $outputPath = base_path($this->getOutputPath());

        return $outputPath.DIRECTORY_SEPARATOR.$dtoClassName.'.php';
    }

    /**
     * Generate DTO class name from Form Request class name.
     */
    private function generateDtoClassName(string $formRequestClassName): string
    {
        $suffix = $this->getFormRequestSuffix();
        $dtoSuffix = $this->getDtoSuffix();

        if (str_ends_with($formRequestClassName, $suffix)) {
            $baseName = substr($formRequestClassName, 0, -strlen($suffix));
        } else {
            $baseName = $formRequestClassName;
        }

        return $baseName.$dtoSuffix;
    }

    /**
     * Generate constructor parameters code.
     *
     * @param  array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>  $fields
     */
    private function generateConstructorParameters(array $fields): string
    {
        if ($fields === []) {
            return '';
        }

        $params = [];
        $phpDocParams = [];
        $readonly = $this->isReadonly() ? 'readonly ' : '';
        $hasComplexTypes = false;

        foreach ($fields as $field) {
            $originalType = $field['type'];
            $phpType = $this->formatType($field);
            $name = $field['name'];
            $default = $this->formatDefaultValue($field);

            // Generate PHP parameter
            $param = "        public {$readonly}{$phpType} \${$name}";

            if ($default !== null) {
                $param .= " = {$default}";
            }

            $params[] = $param;

            // Check if this is a complex type that needs PHPDoc
            if ($this->isComplexType($originalType)) {
                $hasComplexTypes = true;
                $phpDocType = $field['nullable'] ? "{$originalType}|null" : $originalType;
                $phpDocParams[] = "     * @param {$phpDocType} \${$name}";
            }
        }

        $paramString = implode(",\n", $params);

        // Add PHPDoc if we have complex types
        if ($hasComplexTypes) {
            $phpDocString = "    /**\n".implode("\n", $phpDocParams)."\n     */\n";

            return $phpDocString."    public function __construct(\n".$paramString;
        }

        return $paramString;
    }

    /**
     * Check if a type is complex and needs PHPDoc annotation.
     */
    private function isComplexType(string $type): bool
    {
        return str_starts_with($type, 'array<') ||
               str_starts_with($type, 'array{') ||
               str_contains($type, '\\') && $type !== 'string' && $type !== 'int' && $type !== 'float' && $type !== 'bool' && $type !== 'array';
    }

    /**
     * Generate fromRequest method code.
     *
     * @param  array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>  $fields
     */
    private function generateFromRequestMethod(array $fields, string $formRequestClass): string
    {
        if ($fields === [] || ! $this->shouldGenerateFromRequest()) {
            return '';
        }

        $assignments = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $default = $this->formatDefaultValueForMethod($field);

            if ($default !== null) {
                $assignments[] = "            {$name}: \$request->validated('{$name}', {$default})";
            } else {
                $assignments[] = "            {$name}: \$request->validated('{$name}')";
            }
        }

        $assignmentString = implode(",\n", $assignments);

        return "    public static function fromRequest({$formRequestClass} \$request): self
    {
        return new self(
{$assignmentString},
        );
    }";
    }

    /**
     * Format field type for PHP code (simplified for valid PHP types).
     *
     * @param  array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}  $field
     */
    private function formatType(array $field): string
    {
        $type = $this->getPhpType($field['type']);

        if ($field['nullable']) {
            return "?{$type}";
        }

        return $type;
    }

    /**
     * Get simplified PHP type (removes complex array notation).
     */
    private function getPhpType(string $type): string
    {
        // Convert complex array types to simple 'array'
        if (str_starts_with($type, 'array<') || str_starts_with($type, 'array{')) {
            return 'array';
        }

        // Keep other types as is
        return $type;
    }

    /**
     * Format default value for constructor parameter.
     *
     * @param  array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}  $field
     */
    private function formatDefaultValue(array $field): ?string
    {
        if (! $field['has_default']) {
            return null;
        }

        $value = $field['default_value'];

        if ($value === null) {
            return 'null';
        }

        return match ($field['type']) {
            'string' => "'{$value}'",
            'int', 'float' => (string) $value,
            'bool' => $value ? 'true' : 'false',
            'array' => $this->formatArrayDefault($value),
            default => is_array($value) ? $this->formatArrayDefault($value) : "'{$value}'",
        };
    }

    /**
     * Format default value for fromRequest method.
     *
     * @param  array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}  $field
     */
    private function formatDefaultValueForMethod(array $field): ?string
    {
        if (! $field['has_default']) {
            return null;
        }

        $value = $field['default_value'];

        if ($value === null) {
            return 'null';
        }

        return match ($field['type']) {
            'string' => "'{$value}'",
            'int', 'float' => (string) $value,
            'bool' => $value ? 'true' : 'false',
            'array' => $this->formatArrayDefault($value),
            default => is_array($value) ? $this->formatArrayDefault($value) : "'{$value}'",
        };
    }

    /**
     * Format array default value.
     */
    private function formatArrayDefault(mixed $value): string
    {
        if (empty($value)) {
            return '[]';
        }

        if (is_array($value)) {
            return '['.implode(', ', array_map(fn ($item) => is_string($item) ? "'{$item}'" : $item, $value)).']';
        }

        return '[]';
    }

    /**
     * Get DTO namespace from config.
     */
    private function getDtoNamespace(): string
    {
        return config('laravel-dto.namespace', 'App\\DTOs');
    }

    /**
     * Get output path from config.
     */
    private function getOutputPath(): string
    {
        return config('laravel-dto.output_path', 'app/DTOs');
    }

    /**
     * Get Form Request suffix from config.
     */
    private function getFormRequestSuffix(): string
    {
        return config('laravel-dto.form_request_suffix', 'Request');
    }

    /**
     * Get DTO suffix from config.
     */
    private function getDtoSuffix(): string
    {
        return config('laravel-dto.dto_suffix', 'Data');
    }

    /**
     * Check if DTOs should be readonly.
     */
    private function isReadonly(): bool
    {
        return config('laravel-dto.readonly', true);
    }

    /**
     * Check if fromRequest method should be generated.
     */
    private function shouldGenerateFromRequest(): bool
    {
        return config('laravel-dto.generate_from_request_method', true);
    }

    /**
     * Generate Form Request enhancement code.
     *
     * @param  array{class_name: string, namespace: string, short_name: string, form_request_class: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, custom_dto_class: string|null}  $parsedData  Parsed Form Request data
     * @return string Generated PHP code to add to Form Request
     */
    public function generateFormRequestEnhancement(array $parsedData): string
    {
        $formRequestClass = $parsedData['class_name'];
        $stubPath = __DIR__.'/../../stubs/form-request-method.stub';

        if (! file_exists($stubPath)) {
            throw new \RuntimeException("Form Request stub file not found: {$stubPath}");
        }

        $stubContent = file_get_contents($stubPath);

        $replacements = [
            '{{ form_request_class }}' => $formRequestClass,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stubContent);
    }

    /**
     * Get the full DTO class name including namespace.
     */
    public function getDtoFullClassName(string $formRequestClassName, ?string $customDtoClass = null): string
    {
        $dtoNamespace = $this->getDtoNamespace();
        $dtoClassName = $customDtoClass ? class_basename($customDtoClass) : $this->generateDtoClassName($formRequestClassName);

        return $dtoNamespace.'\\'.$dtoClassName;
    }

    /**
     * Check if Form Request already has DTO functionality.
     */
    public function hasFormRequestDtoFunctionality(string $formRequestPath): bool
    {
        if (! file_exists($formRequestPath)) {
            return false;
        }

        $content = file_get_contents($formRequestPath);

        return str_contains($content, 'ConvertsToDto') ||
               str_contains($content, 'HasDto') ||
               str_contains($content, 'toDto()');
    }
}
