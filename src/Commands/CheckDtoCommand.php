<?php

declare(strict_types=1);

namespace JulioCavallari\LaravelDto\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JulioCavallari\LaravelDto\Generators\DtoGenerator;
use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

/**
 * Check DTO Command
 *
 * Artisan command to check which Form Requests don't have corresponding DTOs.
 */
class CheckDtoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dto:check 
                            {--missing : Show only Form Requests without DTOs}
                            {--existing : Show only Form Requests with DTOs}
                            {--details : Show detailed information about each Form Request}';

    /**
     * The console command description.
     */
    protected $description = 'Check which Form Requests have corresponding DTOs and display their DTO class names';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking Form Requests and their corresponding DTOs...');

        $showMissing = $this->option('missing');
        $showExisting = $this->option('existing');
        $details = $this->option('details');

        // If no filter specified, show both
        if (!$showMissing && !$showExisting) {
            $showMissing = true;
            $showExisting = true;
        }

        try {
            $parser = new FormRequestParser();
            $generator = new DtoGenerator();

            $result = $this->analyzeFormRequests($parser, $generator);

            $this->displayResults($result, $showMissing, $showExisting, $details);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Analyze all Form Requests and their DTO status.
     *
     * @return array{missing: array, existing: array, total: int}
     */
    private function analyzeFormRequests(FormRequestParser $parser, DtoGenerator $generator): array
    {
        $formRequestPath = base_path(config('laravel-dto.form_request_path', 'app/Http/Requests'));

        if (!File::exists($formRequestPath)) {
            throw new \Exception("Form Request directory not found: {$formRequestPath}");
        }

        $requestFiles = File::allFiles($formRequestPath);
        $missing = [];
        $existing = [];

        foreach ($requestFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            try {
                $requestInfo = $this->analyzeFormRequest($file->getPathname(), $parser, $generator);

                if ($requestInfo['has_dto']) {
                    $existing[] = $requestInfo;
                } else {
                    $missing[] = $requestInfo;
                }
            } catch (\Exception $e) {
                // Skip files that can't be parsed (not valid Form Requests)
                continue;
            }
        }

        return [
            'missing' => $missing,
            'existing' => $existing,
            'total' => count($missing) + count($existing),
        ];
    }

    /**
     * Analyze a single Form Request file.
     *
     * @return array{name: string, path: string, dto_path: string|null, has_dto: bool, custom_dto: string|null, parseable: bool, fields_count: int, dto_class: string|null}
     */
    private function analyzeFormRequest(string $filePath, FormRequestParser $parser, DtoGenerator $generator): array
    {
        // First check if it's a valid Form Request
        if (!$this->isFormRequest($filePath)) {
            throw new \Exception("Not a Form Request");
        }

        $parsedData = $parser->parse($filePath);
        $className = $parsedData['class_name'];
        $customDtoClass = $parsedData['custom_dto_class'] ?? null;

        // Get expected DTO path and full class name
        $dtoPath = $generator->getDtoPath($className, $customDtoClass);
        $dtoFullClassName = $generator->getDtoFullClassName($className, $customDtoClass);
        $hasDto = File::exists($dtoPath);

        return [
            'name' => $className,
            'path' => $filePath,
            'dto_path' => $hasDto ? $dtoPath : null,
            'expected_dto_path' => $dtoPath,
            'has_dto' => $hasDto,
            'custom_dto' => $customDtoClass,
            'dto_class' => $hasDto ? $dtoFullClassName : null,
            'expected_dto_class' => $dtoFullClassName,
            'parseable' => true,
            'fields_count' => count($parsedData['fields']),
            'namespace' => $parsedData['namespace'],
        ];
    }

    /**
     * Check if a file is a Form Request.
     */
    private function isFormRequest(string $filePath): bool
    {
        $content = File::get($filePath);

        // Basic checks for Form Request patterns
        return str_contains($content, 'extends FormRequest') ||
               str_contains($content, 'extends \\Illuminate\\Foundation\\Http\\FormRequest');
    }

    /**
     * Display the analysis results.
     *
     * @param array{missing: array, existing: array, total: int} $result
     */
    private function displayResults(array $result, bool $showMissing, bool $showExisting, bool $details): void
    {
        $missing = $result['missing'];
        $existing = $result['existing'];
        $total = $result['total'];

        $this->newLine();
        $this->info("📊 Analysis Summary:");
        $this->line("   Total Form Requests: <fg=cyan>{$total}</>");
        $this->line("   With DTOs: <fg=green>" . count($existing) . "</>");
        $this->line("   Without DTOs: <fg=red>" . count($missing) . "</>");

        if ($showMissing && !empty($missing)) {
            $this->newLine();
            $this->error("❌ Form Requests WITHOUT DTOs (" . count($missing) . "):");
            $this->displayFormRequestList($missing, 'red', $details);
        }

        if ($showExisting && !empty($existing)) {
            $this->newLine();
            $this->info("✅ Form Requests WITH DTOs (" . count($existing) . "):");
            $this->displayFormRequestList($existing, 'green', $details);
        }

        if (!empty($missing)) {
            $this->newLine();
            $this->comment("💡 To generate DTOs for missing Form Requests, run:");
            $this->line("   <fg=yellow>php artisan dto:generate</>");

            if (count($missing) === 1) {
                $this->line("   <fg=yellow>php artisan dto:generate {$missing[0]['name']}</>");
            }
        }

        $this->newLine();
    }

    /**
     * Display a list of Form Requests.
     */
    private function displayFormRequestList(array $requests, string $color, bool $details): void
    {
        foreach ($requests as $request) {
            $name = $request['name'];
            $fieldsCount = $request['fields_count'];
            $customDto = $request['custom_dto'] ? ' (Custom DTO)' : '';
            $dtoClass = $request['dto_class'] ?? $request['expected_dto_class'];

            if ($details) {
                $this->line("   <fg={$color}>• {$name}</> ({$fieldsCount} fields){$customDto}");
                $this->line("     Path: <fg=gray>{$request['path']}</>");

                if ($request['has_dto']) {
                    $this->line("     DTO:  <fg=gray>{$request['dto_path']}</>");
                    $this->line("     DTO Class: <fg=cyan>{$dtoClass}</>");
                } else {
                    $this->line("     Expected DTO: <fg=gray>{$request['expected_dto_path']}</>");
                    $this->line("     Expected DTO Class: <fg=yellow>{$dtoClass}</>");
                }

                $this->line("     Namespace: <fg=gray>{$request['namespace']}</>");
                $this->newLine();
            } else {
                $dtoInfo = $request['has_dto']
                    ? "<fg=cyan>→ {$dtoClass}</>"
                    : "<fg=yellow>→ {$dtoClass} (missing)</>";

                $this->line("   <fg={$color}>• {$name}</> ({$fieldsCount} fields){$customDto}");
                $this->line("     {$dtoInfo}");
            }
        }
    }
}
