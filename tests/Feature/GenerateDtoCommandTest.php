<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('can run dto generate command', function (): void {
    // Create the required directories
    File::ensureDirectoryExists(base_path('app/Http/Requests'));
    File::ensureDirectoryExists(base_path('app/DTOs'));

    $this->artisan('dto:generate --dry-run')
        ->expectsOutput('🚀 Generating DTOs from Form Requests...')
        ->assertExitCode(0);
});

it('shows help for dto generate command', function (): void {
    $this->artisan('dto:generate --help')
        ->assertExitCode(0);
});
