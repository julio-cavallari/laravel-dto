<?php

declare(strict_types=1);

use JulioCavallari\LaravelDto\Attributes\UseDto;
use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

it('can use custom DTO class with UseDto attribute', function (): void {
    // Create a mock Form Request class with UseDto attribute
    $mockFormRequestCode = '<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;
use JulioCavallari\\LaravelDto\\Attributes\\UseDto;
use JulioCavallari\\LaravelDto\\Contracts\\HasDto;
use JulioCavallari\\LaravelDto\\Traits\\ConvertsToDto;

#[UseDto("App\\DTOs\\CustomArticleData")]
class TestCustomRequest extends FormRequest implements HasDto
{
    use ConvertsToDto;

    public function rules(): array
    {
        return [
            "title" => "required|string",
            "content" => "required|string",
        ];
    }
}';

    $tempFile = tempnam(sys_get_temp_dir(), 'custom_request_test');
    file_put_contents($tempFile, $mockFormRequestCode);

    $parser = new FormRequestParser;
    $result = $parser->parse($tempFile);

    expect($result)->toHaveKey('custom_dto_class');
    expect($result['custom_dto_class'])->toBe('App\\DTOs\\CustomArticleData');
    expect($result['class_name'])->toBe('TestCustomRequest');

    // Clean up
    unlink($tempFile);
});

it('falls back to null when no UseDto attribute is present', function (): void {
    $mockFormRequestCode = '<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class TestNormalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "title" => "required|string",
        ];
    }
}';

    $tempFile = tempnam(sys_get_temp_dir(), 'normal_request_test');
    file_put_contents($tempFile, $mockFormRequestCode);

    $parser = new FormRequestParser;
    $result = $parser->parse($tempFile);

    expect($result)->toHaveKey('custom_dto_class');
    expect($result['custom_dto_class'])->toBeNull();

    // Clean up
    unlink($tempFile);
});

it('can create UseDto attribute instance', function (): void {
    $attribute = new UseDto('App\\DTOs\\MyCustomDto');

    expect($attribute->dtoClass)->toBe('App\\DTOs\\MyCustomDto');
});
