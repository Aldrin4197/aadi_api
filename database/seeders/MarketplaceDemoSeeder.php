<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\ServiceListing;
use App\Models\User;
use App\Models\VehicleListing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Explicit opt-in fixtures for the current marketplace, not real offers. */
class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $owner = User::firstOrCreate(['id' => $this->id(0)], [
                'name' => 'Aadi Sample Provider',
                'email' => 'marketplace-demo@aadi.example',
                'password' => 'password',
                'landlord_status' => 'approved',
                'vehicle_owner_status' => 'approved',
                'service_provider_status' => 'approved',
            ]);

            $owner->forceFill(['password'=>'password', 'email_verified_at'=>now()])->save();

            $spaces = [
                ['Studio near the city center', 'apartment', 6500, 11.7753, 124.8861, ['Kitchen', 'Water connection']],
                ['Shared room for students', 'bedspace', 2500, 11.7781, 124.8887, ['Shared kitchen', 'Study area']],
                ['Boarding room with workspace', 'boardingHouse', 3800, 11.7727, 124.8839, ['Desk', 'Shared laundry area']],
                ['Two-bedroom family home', 'house', 12000, 11.7811, 124.8920, ['Two bedrooms', 'Parking area']],
                ['Ground-floor shop space', 'commercial', 18000, 11.7709, 124.8850, ['Street access', 'Open floor plan']],
            ];
            foreach ($spaces as $i => [$title, $type, $price, $lat, $lng, $amenities]) {
                Listing::firstOrCreate(['id' => $this->id(10 + $i)], [
                    'owner_id' => $owner->id, 'owner_name' => $owner->name,
                    'title' => '[Sample] '.$title,
                    'description' => $this->description('Example monthly space rental. Utilities and availability would be confirmed with the owner.'),
                    'category' => 'space_rental', 'type' => $type,
                    'price' => $price, 'currency' => 'PHP', 'status' => 'active',
                    'lat' => $lat, 'lng' => $lng, 'city' => 'Catbalogan',
                    'address' => 'Sample location, Catbalogan, Samar',
                    'photos' => [], 'amenities' => $amenities,
                    'property_verification' => 'none',
                ]);
            }

            $vehicles = [
                ['Passenger van for group trips', 'van', 'Toyota', 'Hiace', 2022, 3500, 15],
                ['Light truck for moving day', 'truck', 'Isuzu', 'Traviz', 2021, 2800, 3],
                ['Multicab for local errands', 'multicab', 'Suzuki', 'Carry', 2020, 1400, 6],
                ['Family SUV for a day out', 'suv', 'Toyota', 'Innova', 2023, 3000, 7],
                ['Scooter for city trips', 'motorcycle', 'Honda', 'Click', 2022, 500, 2],
            ];
            foreach ($vehicles as $i => [$title, $type, $brand, $model, $year, $price, $seats]) {
                // Include trashed rows so reseeding does not recreate removed demos.
                VehicleListing::withTrashed()->firstOrCreate(['id' => $this->id(20 + $i)], [
                    'owner_id' => $owner->id, 'title' => '[Sample] '.$title,
                    'description' => $this->description('Example daily vehicle rental. Fuel, driver arrangements and pickup terms would be confirmed with the owner.'),
                    'vehicle_type' => $type, 'brand' => $brand, 'model' => $model,
                    'year' => $year, 'seating_capacity' => $seats,
                    'price_per_day' => $price, 'currency' => 'PHP', 'status' => 'available',
                    'location' => 'Sample pickup point, Catbalogan, Samar',
                    'latitude' => 11.7740 + $i * 0.0012, 'longitude' => 124.8850 + $i * 0.0010,
                    'images' => [], 'features' => [],
                ]);
            }

            $services = [
                ['Home cleaning visit', 'homeCleaning', 900, 'fixed'],
                ['Minor household repairs', 'repairService', 650, 'fixed'],
                ['Math tutoring session', 'tutoringService', 350, 'hourly'],
                ['Computer setup and troubleshooting', 'itSupport', 750, 'fixed'],
                ['Event setup assistance', 'eventsStaffing', 250, 'hourly'],
            ];
            foreach ($services as $i => [$title, $type, $price, $priceType]) {
                ServiceListing::withTrashed()->firstOrCreate(['id' => $this->id(30 + $i)], [
                    'provider_id' => $owner->id, 'title' => '[Sample] '.$title,
                    'description' => $this->description('Example local service. Scope, materials and scheduling would be agreed with the provider before work begins.'),
                    'service_type' => $type, 'price_amount' => $price, 'price_type' => $priceType,
                    'currency' => 'PHP', 'status' => 'available',
                    'service_area' => 'Sample service area, Catbalogan, Samar',
                    'latitude' => 11.7720 + $i * 0.0015, 'longitude' => 124.8840 + $i * 0.0013,
                    'images' => [],
                ]);
            }
        });

        $this->command?->info('Marketplace samples ready: 5 spaces, 5 vehicles and 5 rakets. Existing rows were preserved.');
    }

    private function id(int $number): string
    {
        // Valid, reserved ULIDs remain stable even when a sample title is edited.
        return str_pad('01JAD1DEM', 20, '0').str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    private function description(string $details): string
    {
        return 'DEMO LISTING — fictional sample for exploring Aadi. Not a real offer. Map locations and prices are illustrative. '.$details;
    }
}
