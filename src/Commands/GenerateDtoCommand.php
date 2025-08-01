<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JulioCavallari\LaravelDto\Generators\DtoGenerator;
use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

/**
 * Generate DTO Command
 *
 * Artisan command to generate DTOs from Form Request classes.
 */
class GenerateDtoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dto:generate 
                            {request? : The specific Form Request class to generate DTO for}
                            {--force : Force regenerate existing DTOs}
                            {--dry-run : Preview changes without writing files}
                            {--enhance-requests : Add toDto() method to Form Requests}';

    /**
     * The console command description.
     */
    protected $description = 'Generate DTOs based on Form Request classes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Generating DTOs from Form Requests...');

        $requestName = $this->argument('request');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        try {
            $parser = new FormRequestParser();
            $generator = new DtoGenerator();

            if ($requestName) {
                // Generate DTO for specific Form Request
                $this->generateSingleDto($requestName, $parser, $generator, $force, $dryRun);
            } else {
                // Generate DTOs for all Form Requests
                $this->generateAllDtos($parser, $generator, $force, $dryRun);
            }

            if (! $dryRun) {
                $this->info('✅ DTO generation completed successfully!');
            } else {
                $this->info('📋 Dry run completed. No files were modified.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error generating DTOs: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Generate DTO for a specific Form Request.
     */
    private function generateSingleDto(
        string $requestName,
        FormRequestParser $parser,
        DtoGenerator $generator,
        bool $force,
        bool $dryRun
    ): void {
        $requestPath = $this->findFormRequestPath($requestName);

        if (! $requestPath) {
            throw new \Exception("Form Request '{$requestName}' not found.");
        }

        $this->processFormRequest($requestPath, $parser, $generator, $force, $dryRun);
    }

    /**
     * Generate DTOs for all Form Requests.
     */
    private function generateAllDtos(
        FormRequestParser $parser,
        DtoGenerator $generator,
        bool $force,
        bool $dryRun
    ): void {
        $formRequestPath = base_path(config('laravel-dto.form_request_path', 'app/Http/Requests'));

        if (! File::exists($formRequestPath)) {
            throw new \Exception("Form Request directory not found: {$formRequestPath}");
        }

        $requestFiles = File::allFiles($formRequestPath);
        $processed = 0;

        foreach ($requestFiles as $file) {
            if ($file->getExtension() === 'php') {
                try {
                    $this->processFormRequest($file->getPathname(), $parser, $generator, $force, $dryRun);
                    $processed++;
                } catch (\Exception $e) {
                    $this->warn("⚠️  Skipped {$file->getFilename()}: {$e->getMessage()} at {$e->getFile()}@{$e->getLine()}");
                }
            }
        }

        $this->info("📊 Processed {$processed} Form Request(s)");
    }

    /**
     * Process a single Form Request file.
     */
    private function processFormRequest(
        string $filePath,
        FormRequestParser $parser,
        DtoGenerator $generator,
        bool $force,
        bool $dryRun
    ): void {
        $className = $this->getClassNameFromFile($filePath);

        if ($this->shouldSkipRequest($className)) {
            $this->line("⏭️  Skipping excluded request: {$className}");

            return;
        }

        $this->line("🔍 Processing: {$className}");

        $parsedData = $parser->parse($filePath);

        // Generate enum files first
        if (!empty($parsedData['enums'])) {
            $this->generateEnumFiles($generator, $parsedData['enums'], $force, $dryRun);
        }

        $dtoCode = $generator->generate($parsedData);

        $dtoPath = $generator->getDtoPath($className, $parsedData['custom_dto_class']);

        if (! $force && File::exists($dtoPath) && ! $dryRun) {
            $this->line("⚠️  DTO already exists: {$className}Data (use --force to overwrite)");

            return;
        }

        // Generate DTO
        $this->generateDtoFile($dtoPath, $dtoCode, $className, $dryRun);

        // Enhance Form Request if requested
        if ($this->option('enhance-requests')) {
            $this->enhanceFormRequest($filePath, $generator, $parsedData, $dryRun);
        }
    }

    /**
     * Generate DTO file.
     */
    private function generateDtoFile(string $dtoPath, string $dtoCode, string $className, bool $dryRun): void
    {
        if ($dryRun) {
            $this->line("📝 Would generate: {$dtoPath}");
            $this->line('--- Generated Code Preview ---');
            $this->line($dtoCode);
            $this->line('--- End Preview ---');
        } else {
            File::ensureDirectoryExists(dirname($dtoPath));
            File::put($dtoPath, $dtoCode);
            $this->line("✅ Generated: {$className}Data");
        }
    }

    /**
     * Enhance Form Request with toDto() functionality.
     *
     * @param  array{class_name: string, namespace: string, short_name: string, form_request_class: string, fields: array<string, array{type: string, nullable: bool, default: mixed, name: string, is_array: bool, has_default: bool, default_value: mixed, rules: array<string>}>, custom_dto_class: string|null, file_path: string}  $parsedData
     */
    private function enhanceFormRequest(string $filePath, DtoGenerator $generator, array $parsedData, bool $dryRun): void
    {
        if ($generator->hasFormRequestDtoFunctionality($filePath)) {
            $this->line('⚠️  Form Request already has DTO functionality');

            return;
        }

        $enhancementCode = $generator->generateFormRequestEnhancement($parsedData);

        if ($dryRun) {
            $this->line('📝 Would enhance Form Request with:');
            $this->line($enhancementCode);
        } else {
            $this->addTraitToFormRequest($filePath, $enhancementCode);
            $this->line('✅ Enhanced Form Request with toDto() method');
        }
    }

    /**
     * Add trait and interface to existing Form Request using the generated enhancement code.
     */
    private function addTraitToFormRequest(string $filePath, string $enhancementCode): void
    {
        File::put($filePath, $enhancementCode);
    }

    /**
     * Find Form Request file path by class name.
     */
    private function findFormRequestPath(string $requestName): ?string
    {
        $formRequestPath = base_path(config('laravel-dto.form_request_path', 'app/Http/Requests'));
        $fileName = $requestName.'.php';

        $files = File::allFiles($formRequestPath);

        foreach ($files as $file) {
            if ($file->getFilename() === $fileName) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Extract class name from file path.
     */
    private function getClassNameFromFile(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Check if a Form Request should be skipped.
     */
    private function shouldSkipRequest(string $className): bool
    {
        $excluded = config('laravel-dto.excluded_requests', []);

        foreach ($excluded as $pattern) {
            if (str_contains((string) $pattern, '*')) {
                $regex = '/^'.str_replace('*', '.*', $pattern).'$/';
                if (preg_match($regex, $className)) {
                    return true;
                }
            } elseif ($pattern === $className) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate enum files from parsed enum data.
     *
     * @param  array<string, array{name: string, values: array<string>, namespace: string}>  $enums
     */
    private function generateEnumFiles(
        DtoGenerator $generator,
        array $enums,
        bool $force,
        bool $dryRun
    ): void {
        $generatedEnums = $generator->generateEnums($enums);

        foreach ($generatedEnums as $enumPath => $enumCode) {
            $enumName = basename($enumPath, '.php');

            if (!$force && File::exists($enumPath) && !$dryRun) {
                $this->line("⚠️  Enum already exists: {$enumName} (use --force to overwrite)");
                continue;
            }

            if ($dryRun) {
                $this->line("📝 Would generate enum: {$enumPath}");
                $this->line('--- Generated Enum Code Preview ---');
                $this->line($enumCode);
                $this->line('--- End Enum Preview ---');
            } else {
                File::ensureDirectoryExists(dirname($enumPath));
                File::put($enumPath, $enumCode);
                $this->line("✅ Generated enum: {$enumName}");
            }
        }
    }
}
