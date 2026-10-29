<?php

namespace App\Http\Controllers\Api;

use App\Enums\LandlordStatus;
use App\Enums\ServiceProviderStatus;
use App\Enums\VehicleOwnerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'name' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'landlord_status' => LandlordStatus::NONE,
            'vehicle_owner_status' => VehicleOwnerStatus::NONE,
            'service_provider_status' => ServiceProviderStatus::NONE,
        ]);

        $verificationSent = true;
        try { event(new \Illuminate\Auth\Events\Registered($user)); }
        catch (\Throwable $exception) { $verificationSent = false; }
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'verificationEmailSent' => $verificationSent,
            'refreshToken' => $token, // Same token for simplicity
            'user' => new UserResource($user->load('badges')),
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'refreshToken' => $token,
            'user' => new UserResource($user->load('badges')),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('badges'));
    }

    public function applyForLandlord(Request $request)
    {
        $user = $request->user();

        if ($user->landlord_status !== LandlordStatus::NONE) {
            return response()->json([
                'message' => 'You have already applied or are already a landlord.',
            ], 400);
        }

        $user->update(['landlord_status' => LandlordStatus::PENDING]);

        return new UserResource($user->fresh()->load('badges'));
    }

    public function approveLandlord(Request $request)
    {
        // This is for demo purposes only - should be admin-only in production
        $user = $request->user();

        if ($user->landlord_status !== LandlordStatus::PENDING) {
            return response()->json([
                'message' => 'No pending landlord application found.',
            ], 400);
        }

        $user->update(['landlord_status' => LandlordStatus::APPROVED]);

        return new UserResource($user->fresh()->load('badges'));
    }

    public function applyForVehicleOwner(Request $request)
    {
        $user = $request->user();

        if ($user->vehicle_owner_status !== VehicleOwnerStatus::NONE) {
            return response()->json([
                'message' => 'You have already applied or are already a vehicle owner.',
            ], 400);
        }

        $validated = $request->validate([
            'businessName' => 'required|string|max:255',
            'licenses' => 'required|array',
            'licenses.*' => 'string',
            'inventory' => 'nullable|array',
            'certificates' => 'nullable|array',
        ]);

        $user->update([
            'vehicle_owner_status' => VehicleOwnerStatus::PENDING,
            'vehicle_owner_business_name' => $validated['businessName'],
            'vehicle_owner_licenses' => $validated['licenses'],
            'vehicle_owner_inventory' => $validated['inventory'] ?? [],
            'vehicle_owner_certificates' => $validated['certificates'] ?? [],
        ]);

        return new UserResource($user->fresh()->load('badges'));
    }

    public function approveVehicleOwner(Request $request, ?string $userId = null)
    {
        // Demo self-approval path for local/mobile integration.
        if ($userId === null) {
            $user = $request->user();

            if ($user->vehicle_owner_status !== VehicleOwnerStatus::PENDING) {
                return response()->json([
                    'message' => 'No pending Aadi Haul application found.',
                ], 400);
            }

            $user->update(['vehicle_owner_status' => VehicleOwnerStatus::APPROVED]);

            return new UserResource($user->fresh()->load('badges'));
        }

        if (!($request->user()->is_admin ?? false)) {
            abort(403, 'Only administrators can approve vehicle owners.');
        }

        $user = User::findOrFail($userId);

        if ($user->vehicle_owner_status !== VehicleOwnerStatus::PENDING) {
            return response()->json([
                'message' => 'User does not have a pending vehicle owner application.',
            ], 400);
        }

        $user->update(['vehicle_owner_status' => VehicleOwnerStatus::APPROVED]);

        return new UserResource($user->fresh()->load('badges'));
    }

    public function applyForServiceProvider(Request $request)
    {
        $user = $request->user();

        if ($user->service_provider_status !== ServiceProviderStatus::NONE) {
            return response()->json([
                'message' => 'You have already applied or are already a service provider.',
            ], 400);
        }

        $validated = $request->validate([
            'businessName' => 'required|string|max:255',
            'skills' => 'required|array',
            'skills.*' => 'string',
            'certifications' => 'nullable|array',
            'serviceArea' => 'required|string|max:255',
            'yearsExperience' => 'required|integer|min:0',
        ]);

        $user->update([
            'service_provider_status' => ServiceProviderStatus::PENDING,
            'service_provider_business_name' => $validated['businessName'],
            'service_provider_skills' => $validated['skills'],
            'service_provider_certifications' => $validated['certifications'] ?? [],
            'service_provider_service_area' => $validated['serviceArea'],
            'service_provider_years_experience' => $validated['yearsExperience'],
        ]);

        return new UserResource($user->fresh()->load('badges'));
    }

    public function approveServiceProvider(Request $request, ?string $userId = null)
    {
        // Demo self-approval path for local/mobile integration.
        if ($userId === null) {
            $user = $request->user();

            if ($user->service_provider_status !== ServiceProviderStatus::PENDING) {
                return response()->json([
                    'message' => 'No pending Aadi Raketeer application found.',
                ], 400);
            }

            $user->update(['service_provider_status' => ServiceProviderStatus::APPROVED]);

            return new UserResource($user->fresh()->load('badges'));
        }

        if (!($request->user()->is_admin ?? false)) {
            abort(403, 'Only administrators can approve service providers.');
        }

        $user = User::findOrFail($userId);

        if ($user->service_provider_status !== ServiceProviderStatus::PENDING) {
            return response()->json([
                'message' => 'User does not have a pending service provider application.',
            ], 400);
        }

        $user->update(['service_provider_status' => ServiceProviderStatus::APPROVED]);

        return new UserResource($user->fresh()->load('badges'));
    }
}
