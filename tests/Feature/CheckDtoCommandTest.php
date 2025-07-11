<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    // Ensure directories exist
    File::ensureDirectoryExists(base_path('app/Http/Requests'));
    File::ensureDirectoryExists(base_path('app/DTOs'));
});

afterEach(function (): void {
    // Clean up test files
    $testFiles = [
        base_path('app/Http/Requests/TestFormRequest.php'),
        base_path('app/Http/Requests/AnotherFormRequest.php'),
        base_path('app/DTOs/TestFormData.php'),
    ];

    foreach ($testFiles as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

it('can run dto check command', function (): void {
    $this->artisan('dto:check')
        ->expectsOutput('🔍 Checking Form Requests and their corresponding DTOs...')
        ->assertExitCode(0);
});

it('shows help for dto check command', function (): void {
    $this->artisan('dto:check --help')
        ->assertExitCode(0);
});

it('detects form requests without DTOs', function (): void {
    // Create a test Form Request without DTO
    $formRequestContent = '<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "email" => "required|email",
        ];
    }
}';

    File::put(base_path('app/Http/Requests/TestFormRequest.php'), $formRequestContent);

    $this->artisan('dto:check --missing')
        ->expectsOutput('🔍 Checking Form Requests and their corresponding DTOs...')
        ->expectsOutputToContain('Form Requests WITHOUT DTOs')
        ->expectsOutputToContain('TestFormRequest')
        ->assertExitCode(0);
});

it('detects form requests with DTOs', function (): void {
    // Create a test Form Request
    $formRequestContent = '<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "email" => "required|email",
        ];
    }
}';

    File::put(base_path('app/Http/Requests/TestFormRequest.php'), $formRequestContent);

    // Create corresponding DTO
    $dtoContent = '<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class TestFormData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}';

    File::put(base_path('app/DTOs/TestFormData.php'), $dtoContent);

    $this->artisan('dto:check --existing')
        ->expectsOutput('🔍 Checking Form Requests and their corresponding DTOs...')
        ->expectsOutputToContain('Form Requests WITH DTOs')
        ->expectsOutputToContain('TestFormRequest')
        ->assertExitCode(0);
});

it('shows detailed information when requested', function (): void {
    // Create a test Form Request
    $formRequestContent = '<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "email" => "required|email",
        ];
    }
}';

    File::put(base_path('app/Http/Requests/TestFormRequest.php'), $formRequestContent);

    $this->artisan('dto:check --details')
        ->expectsOutput('🔍 Checking Form Requests and their corresponding DTOs...')
        ->expectsOutputToContain('TestFormRequest')
        ->assertExitCode(0);
});

it('shows summary statistics', function (): void {
    // Create multiple test Form Requests
    $formRequest1 = '<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return ["name" => "required|string"];
    }
}';

    $formRequest2 = '<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnotherFormRequest extends FormRequest
{
    public function rules(): array
    {
        return ["email" => "required|email"];
    }
}';

    File::put(base_path('app/Http/Requests/TestFormRequest.php'), $formRequest1);
    File::put(base_path('app/Http/Requests/AnotherFormRequest.php'), $formRequest2);

    // Create DTO for only one of them
    $dtoContent = '<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class TestFormData
{
    public function __construct(public string $name) {}
}';

    File::put(base_path('app/DTOs/TestFormData.php'), $dtoContent);

    $this->artisan('dto:check')
        ->expectsOutput('🔍 Checking Form Requests and their corresponding DTOs...')
        ->expectsOutputToContain('Analysis Summary:')
        ->expectsOutputToContain('Total Form Requests: 2')
        ->expectsOutputToContain('With DTOs: 1')
        ->expectsOutputToContain('Without DTOs: 1')
        ->assertExitCode(0);
});
