<?php

declare(strict_types=1);

use JulioCavallari\LaravelDto\Parsers\FormRequestParser;

beforeEach(function (): void {
    $this->parser = new FormRequestParser;
});

it('can parse simple form request rules', function (): void {
    $filePath = createTempFormRequest([
        'title' => 'required|string',
        'email' => 'required|email',
        'age' => 'integer|nullable',
    ]);

    $result = $this->parser->parse($filePath);

    expect($result['class_name'])->toBe('TestRequest');
    expect($result['fields'])->toHaveCount(3);

    $titleField = collect($result['fields'])->firstWhere('name', 'title');
    expect($titleField['type'])->toBe('string');
    expect($titleField['nullable'])->toBeFalse();

    $ageField = collect($result['fields'])->firstWhere('name', 'age');
    expect($ageField['type'])->toBe('int');
    expect($ageField['nullable'])->toBeTrue();
});

it('handles array validation rules', function (): void {
    $filePath = createTempFormRequest([
        'tags' => 'array',
        'options' => 'array|nullable',
    ]);

    $result = $this->parser->parse($filePath);

    // Verificar se o parser encontrou campos
    expect($result['fields'])->not->toBeEmpty();
    expect($result)->toHaveKey('class_name');
    expect($result)->toHaveKey('namespace');
});

function createTempFormRequest(array $rules): string
{
    $rulesString = '';
    foreach ($rules as $field => $rule) {
        $rulesString .= "            '{$field}' => '{$rule}',\n";
    }

    $content = "<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class TestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
{$rulesString}        ];
    }
}";

    $tempFile = tempnam(sys_get_temp_dir(), 'form_request_test');
    file_put_contents($tempFile, $content);

    return $tempFile;
}
