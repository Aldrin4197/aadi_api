<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PageAvailability
{
    public const PAGES = [
        'home' => ['name' => 'Home', 'path' => '/', 'description' => 'The public landing page.'],
        'explore' => ['name' => 'Explore', 'path' => '/explore', 'description' => 'The marketplace map and search page.'],
        'providers' => ['name' => 'Provider profiles', 'path' => '/providers/{user}', 'description' => 'Public provider profiles.'],
    ];

    public function all(): array
    {
        $settings = DB::table('page_settings')->pluck('enabled', 'key');

        return collect(self::PAGES)->map(fn ($page, $key) => [
            'key' => $key, ...$page, 'enabled' => (bool) ($settings[$key] ?? true),
        ])->values()->all();
    }

    public function enabled(string $key): bool
    {
        abort_unless(isset(self::PAGES[$key]), 404);
        $enabled = DB::table('page_settings')->where('key', $key)->value('enabled');

        return $enabled === null || (bool) $enabled;
    }

    public function update(string $key, bool $enabled): void
    {
        abort_unless(isset(self::PAGES[$key]), 404);
        DB::table('page_settings')->upsert([
            ['key' => $key, 'enabled' => $enabled, 'created_at' => now(), 'updated_at' => now()],
        ], ['key'], ['enabled', 'updated_at']);
    }
}
