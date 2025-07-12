<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Example DTO that would be generated from CreateUserProfileRequest
 *
 * This demonstrates how the new array and nested field functionality works:
 *
 * 1. Fields with asterisk notation (e.g., 'tags.*') become array<int, type>
 * 2. Fields with dot notation (e.g., 'profile.first_name') become array{field: type, ...}
 */
readonly class CreateUserProfileData
{
    public function __construct(
        // Regular fields
        public string $name,
        public string $email,

        // Array fields with asterisk notation -> array<int, type>
        public array $tags,      // array<int, string>
        public array $hobbies,   // array<int, string>
        public array $skills,    // array<int, string>

        // Nested fields with dot notation -> array{field: type, ...}
        public array $profile,   // array{first_name: string, last_name: string, birth_date: ?Carbon\Carbon, bio: ?string}
        public array $address,   // array{street: string, city: string, state: string, zip_code: string, country: string}
        public array $preferences, // array{theme: string, language: string, notifications: bool, timezone: ?string}
        public array $social_links, // array{twitter: ?string, linkedin: ?string, github: ?string}
    ) {}

    /**
     * Example of how the data would look when instantiated:
     */
    public static function example(): self
    {
        return new self(
            name: 'John Doe',
            email: 'john@example.com',

            // Array fields
            tags: ['developer', 'php', 'laravel'],
            hobbies: ['reading', 'coding', 'gaming'],
            skills: ['PHP', 'JavaScript', 'Python'],

            // Nested objects
            profile: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => null,
                'bio' => 'Software developer passionate about clean code',
            ],
            address: [
                'street' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'zip_code' => '12345',
                'country' => 'USA',
            ],
            preferences: [
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => true,
                'timezone' => 'America/New_York',
            ],
            social_links: [
                'twitter' => 'https://twitter.com/johndoe',
                'linkedin' => 'https://linkedin.com/in/johndoe',
                'github' => 'https://github.com/johndoe',
            ]
        );
    }
}
