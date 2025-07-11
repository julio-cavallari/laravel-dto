<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Templates;

/**
 * DTO Template
 *
 * Provides templates for generating DTO class code using stub files.
 */
class DtoTemplate
{
    /**
     * Render DTO class template with provided data.
     *
     * @param  array{namespace: string, class_name: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, generate_from_request: bool, from_request_method: string, use_imports: bool, form_request_class: string, form_request_namespace: string, constructor_params: string, readonly: bool}  $data  Template data
     * @return string Generated PHP code
     */
    public function render(array $data): string
    {
        $stubContent = $this->getStubContent();

        $replacements = [
            '{{ namespace }}' => $data['namespace'],
            '{{ use_statements }}' => $this->generateUseStatements($data),
            '{{ class_declaration }}' => $this->generateClassDeclaration($data),
            '{{ constructor }}' => $this->generateConstructor($data),
            '{{ from_request_method }}' => $data['generate_from_request'] ? $data['from_request_method'] : '',
            '{{ timestamp }}' => date('Y-m-d H:i:s'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stubContent);
    }

    /**
     * Get the stub file content.
     */
    private function getStubContent(): string
    {
        $stubPath = __DIR__.'/../../stubs/dto.stub';

        if (! file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    /**
     * Generate use statements.
     *
     * @param  array{namespace: string, class_name: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, generate_from_request: bool, from_request_method: string, use_imports: bool, form_request_class: string, form_request_namespace: string, constructor_params: string, readonly: bool}  $data
     */
    private function generateUseStatements(array $data): string
    {
        $uses = [];

        if ($data['generate_from_request']) {
            $uses[] = "use {$data['form_request_namespace']}\\{$data['form_request_class']};";
        }

        return $uses === [] ? '' : implode("\n", $uses)."\n";
    }

    /**
     * Generate class declaration.
     *
     * @param  array{namespace: string, class_name: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, generate_from_request: bool, from_request_method: string, use_imports: bool, form_request_class: string, form_request_namespace: string, constructor_params: string, readonly: bool}  $data
     */
    private function generateClassDeclaration(array $data): string
    {
        $readonly = $data['readonly'] ? 'readonly ' : '';

        return "final {$readonly}class {$data['class_name']}";
    }

    /**
     * Generate constructor.
     *
     * @param  array{namespace: string, class_name: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, generate_from_request: bool, from_request_method: string, use_imports: bool, form_request_class: string, form_request_namespace: string, constructor_params: string, readonly: bool}  $data
     */
    private function generateConstructor(array $data): string
    {
        if (empty($data['constructor_params'])) {
            return "    public function __construct()\n    {\n    }";
        }

        return "    public function __construct(\n{$data['constructor_params']},\n    ) {\n    }";
    }
}
