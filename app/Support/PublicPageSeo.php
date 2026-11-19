<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicPageSeo
{
    public static function forRequest(Request $request): array
    {
        $path = $request->path();
        $pages = [
            '/' => ['Find properties, vehicles & rakets nearby — Aadi', 'Find rental spaces, vehicles, and local services near you with Aadi. Browse the map, compare listed rates, and discover nearby providers.'],
            'explore' => ['Find properties, vehicles & rakets nearby — Aadi', 'Find rental spaces, vehicles, and local services near you with Aadi. Browse the map, compare listed rates, and discover nearby providers.'],
            'privacy-policy' => ['Privacy Policy — Aadi', 'Read how Aadi handles personal information and privacy requests.'],
            'terms-of-service' => ['Terms of Service — Aadi', 'Read the terms for using Aadi and arranging transactions with other users.'],
            'data-deletion' => ['Data Deletion Instructions — Aadi', 'Learn how to delete your Aadi account and personal data.'],
        ];
        $public = isset($pages[$path]);
        $baseUrl = rtrim(config('app.url'), '/');

        return [
            'public' => $public,
            'robots' => $public ? ($request->query() ? 'noindex, follow' : 'index, follow') : 'noindex, nofollow',
            'title' => $pages[$path][0] ?? $pages['/'][0],
            'description' => $pages[$path][1] ?? $pages['/'][1],
            'canonical' => $public ? $baseUrl.($path === '/' ? '/' : '/'.$path) : null,
            'image' => $baseUrl.'/images/logos/Aadi_Color_w_tagname.png',
        ];
    }
}
