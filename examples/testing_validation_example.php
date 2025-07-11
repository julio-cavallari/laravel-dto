<?php

declare(strict_types=1);

/**
 * Testing Example - How to test Form Request validation with DTOs
 */

use App\Http\Requests\CreateArticleRequest;
use Tests\TestCase;

class CreateArticleRequestTest extends TestCase
{
    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/articles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /** @test */
    public function it_validates_field_lengths()
    {
        $response = $this->postJson('/articles', [
            'title' => str_repeat('a', 256), // Too long (max 255)
            'content' => 'short',           // Too short (min 10)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /** @test */
    public function it_creates_dto_with_valid_data()
    {
        $validData = [
            'title' => 'Test Article',
            'content' => 'This is a long enough content for the article',
            'excerpt' => 'Short description',
            'tags' => ['laravel', 'php'],
            'published' => true,
        ];

        $response = $this->postJson('/articles', $validData);

        $response->assertStatus(201);

        // You can also test the DTO creation directly
        $request = CreateArticleRequest::createFrom(
            request()->merge($validData)
        );

        // Manual validation for testing
        $this->assertTrue($request->authorize());

        $validator = \Validator::make($validData, $request->rules());
        $this->assertFalse($validator->fails());

        // Test DTO creation
        $dto = $request->toDto();
        $this->assertInstanceOf(\App\DTOs\CreateArticleData::class, $dto);
        $this->assertEquals('Test Article', $dto->title);
        $this->assertEquals('This is a long enough content for the article', $dto->content);
        $this->assertEquals('Short description', $dto->excerpt);
        $this->assertEquals(['laravel', 'php'], $dto->tags);
        $this->assertTrue($dto->published);
    }

    /** @test */
    public function it_handles_nullable_fields_correctly()
    {
        $validData = [
            'title' => 'Test Article',
            'content' => 'This is a long enough content for the article',
            // excerpt is nullable - not provided
            // tags is array with default empty
            // published is boolean with default false
        ];

        $request = CreateArticleRequest::createFrom(
            request()->merge($validData)
        );

        $dto = $request->toDto();

        $this->assertNull($dto->excerpt);
        $this->assertEquals([], $dto->tags);
        $this->assertFalse($dto->published);
    }
}

// Feature Test Example
class ArticleControllerTest extends TestCase
{
    /** @test */
    public function it_creates_article_with_valid_request()
    {
        $validData = [
            'title' => 'My New Article',
            'content' => 'This is the content of my new article with enough characters',
            'excerpt' => 'A brief description',
            'tags' => ['tutorial', 'laravel'],
            'published' => true,
        ];

        $response = $this->postJson('/articles', $validData);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'My New Article',
                'content' => 'This is the content of my new article with enough characters',
                'excerpt' => 'A brief description',
                'published' => true,
            ]);

        $this->assertDatabaseHas('articles', [
            'title' => 'My New Article',
            'published' => true,
        ]);
    }

    /** @test */
    public function it_rejects_invalid_data()
    {
        $invalidData = [
            'title' => '', // Empty title
            'content' => 'x', // Too short
            'tags' => ['a-very-long-tag-that-exceeds-the-maximum-length-limit'], // Tag too long
        ];

        $response = $this->postJson('/articles', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'content',
                    'tags.0',
                ],
            ]);

        // Ensure no article was created
        $this->assertDatabaseMissing('articles', [
            'title' => '',
        ]);
    }
}

/**
 * Key Testing Points:
 *
 * 1. ✅ Test validation rules work correctly
 * 2. ✅ Test successful DTO creation with valid data
 * 3. ✅ Test error responses with invalid data
 * 4. ✅ Test nullable/default field handling
 * 5. ✅ Test business logic after validation passes
 * 6. ✅ Ensure database operations only happen with valid data
 */
