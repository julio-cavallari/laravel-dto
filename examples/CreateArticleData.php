<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\CreateArticleRequest;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * Generated DTO class
 *
 * This class was automatically generated from a Form Request.
 *
 * @generated 2025-07-10 12:00:00
 */
final readonly class CreateArticleData
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $excerpt,
        public array $tags,
        public bool $published,
        public ?Carbon $publish_date,
        public int $author_id,
        public array $category_ids = [],
        public ?UploadedFile $featured_image = null,
        public ?string $meta_description = null,
        public ?string $slug = null,
    ) {}

    public static function fromRequest(CreateArticleRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            excerpt: $request->validated('excerpt', null),
            tags: $request->validated('tags', []),
            published: $request->validated('published', false),
            publish_date: $request->validated('publish_date', null),
            author_id: $request->validated('author_id'),
            category_ids: $request->validated('category_ids', []),
            featured_image: $request->validated('featured_image', null),
            meta_description: $request->validated('meta_description', null),
            slug: $request->validated('slug', null),
        );
    }
}
