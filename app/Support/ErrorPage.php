<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorPage
{
    public static function response(Request $request, int $status, bool $disabled = false, array $headers = []): Response
    {
        [$title, $message] = $disabled
            ? ['Page temporarily unavailable', 'This page is currently unavailable. Please try again later.']
            : match ($status) {
                403 => ['Access denied', 'You do not have permission to view this page.'],
                404 => ['Page not found', 'We could not find the page you requested.'],
                419 => ['Session expired', 'Your session has expired. Refresh the page and try again.'],
                429 => ['Too many requests', 'Please wait a moment before trying again.'],
                503 => ['Temporarily unavailable', 'Aadi is temporarily unavailable. Please try again later.'],
                default => ['Something went wrong', 'We could not complete your request. Please try again later.'],
            };

        $headers['Cache-Control'] = 'no-store, private';
        $headers['X-Robots-Tag'] = 'noindex, nofollow';
        if ($disabled) $headers['Retry-After'] = '3600';

        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => $message], $status, $headers);
        }

        if ($request->header('X-Inertia')) {
            return response()->json([
                'component' => 'Error',
                'props' => ['status' => $status, 'title' => $title, 'message' => $message,
                    'errors' => (object) [], 'seo' => ['public' => false, 'robots' => 'noindex, nofollow']],
                'url' => $request->getRequestUri(),
                'version' => $request->header('X-Inertia-Version'),
            ], $status, [...$headers, 'X-Inertia' => 'true', 'Vary' => 'X-Inertia']);
        }

        return response()->view('errors.page', compact('status', 'title', 'message'), $status, $headers);
    }
}
