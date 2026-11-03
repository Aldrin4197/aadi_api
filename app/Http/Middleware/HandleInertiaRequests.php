<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => ['success' => fn () => $request->session()->get('success')],
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'is_admin', 'avatar_url']),
                'onboarding' => $request->user() ? ['status'=>$request->user()->onboarding_status, 'data'=>$request->user()->onboarding_data] : null,
                'providerRoles' => $request->user() ? [
                    'spaces' => $request->user()->landlord_status?->value ?? 'none',
                    'haul' => $request->user()->vehicle_owner_status?->value ?? 'none',
                    'raketeer' => $request->user()->service_provider_status?->value ?? 'none',
                ] : [],
            ],
        ];
    }
}
