<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Example Form Request demonstrating the new array and nested field functionality
 */
class CreateUserProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Regular fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',

            // Array fields with asterisk notation - become array<int, type>
            'tags' => 'array',
            'tags.*' => 'string|max:50',

            'hobbies' => 'array',
            'hobbies.*' => 'string|max:100',

            'skills' => 'array',
            'skills.*' => 'string|max:75',

            // Nested fields with dot notation - become array{field: type, ...}
            'profile' => 'array',
            'profile.first_name' => 'required|string|max:100',
            'profile.last_name' => 'required|string|max:100',
            'profile.birth_date' => 'nullable|date',
            'profile.bio' => 'nullable|string|max:1000',

            'address' => 'array',
            'address.street' => 'required|string|max:255',
            'address.city' => 'required|string|max:100',
            'address.state' => 'required|string|max:50',
            'address.zip_code' => 'required|string|max:10',
            'address.country' => 'required|string|max:50',

            'preferences' => 'array',
            'preferences.theme' => 'string|in:light,dark,auto',
            'preferences.language' => 'string|in:en,pt,es,fr',
            'preferences.notifications' => 'boolean',
            'preferences.timezone' => 'nullable|string',

            'social_links' => 'array',
            'social_links.twitter' => 'nullable|url',
            'social_links.linkedin' => 'nullable|url',
            'social_links.github' => 'nullable|url',
        ];
    }
}
