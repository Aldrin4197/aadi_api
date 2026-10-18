<?php

namespace Database\Seeders;

use App\Enums\BadgeType;
use App\Enums\LandlordStatus;
use App\Enums\ListingCategory;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\VerificationStatus;
use App\Enums\ViewingStatus;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\ListingView;
use App\Models\TrustBadge;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Database\Seeder;

class AadiSeeder extends Seeder
{
    public function run(): void
    {
        // Create test tenants
        $tenant1 = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@test.com',
            'password' => 'password',
            'landlord_status' => LandlordStatus::NONE,
            'phone' => '+639171234567',
            'trust_score' => 85,
            'rating_average' => 4.5,
            'rating_count' => 12,
        ]);

        TrustBadge::create([
            'user_id' => $tenant1->id,
            'type' => BadgeType::EMAIL_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        TrustBadge::create([
            'user_id' => $tenant1->id,
            'type' => BadgeType::PHONE_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        $tenant2 = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@test.com',
            'password' => 'password',
            'landlord_status' => LandlordStatus::NONE,
            'phone' => '+639181234567',
            'trust_score' => 72,
            'rating_average' => 4.2,
            'rating_count' => 8,
        ]);

        TrustBadge::create([
            'user_id' => $tenant2->id,
            'type' => BadgeType::EMAIL_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        // Create test landlords
        $landlord1 = User::create([
            'name' => 'Pedro Reyes',
            'email' => 'pedro@test.com',
            'password' => 'password',
            'landlord_status' => LandlordStatus::APPROVED,
            'phone' => '+639191234567',
            'trust_score' => 92,
            'rating_average' => 4.8,
            'rating_count' => 45,
        ]);

        TrustBadge::create([
            'user_id' => $landlord1->id,
            'type' => BadgeType::EMAIL_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        TrustBadge::create([
            'user_id' => $landlord1->id,
            'type' => BadgeType::PHONE_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        TrustBadge::create([
            'user_id' => $landlord1->id,
            'type' => BadgeType::LANDLORD_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        TrustBadge::create([
            'user_id' => $landlord1->id,
            'type' => BadgeType::RESPONSIVE,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        $landlord2 = User::create([
            'name' => 'Ana Garcia',
            'email' => 'ana@test.com',
            'password' => 'password',
            'landlord_status' => LandlordStatus::APPROVED,
            'phone' => '+639201234567',
            'trust_score' => 88,
            'rating_average' => 4.6,
            'rating_count' => 32,
        ]);

        TrustBadge::create([
            'user_id' => $landlord2->id,
            'type' => BadgeType::EMAIL_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        TrustBadge::create([
            'user_id' => $landlord2->id,
            'type' => BadgeType::LANDLORD_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        // Create pending landlord
        $pendingLandlord = User::create([
            'name' => 'Carlos Tan',
            'email' => 'carlos@test.com',
            'password' => 'password',
            'landlord_status' => LandlordStatus::PENDING,
            'phone' => '+639211234567',
            'trust_score' => 65,
        ]);

        TrustBadge::create([
            'user_id' => $pendingLandlord->id,
            'type' => BadgeType::EMAIL_VERIFIED,
            'status' => VerificationStatus::VERIFIED,
            'granted_at' => now(),
        ]);

        // Create listings in Manila area
        Listing::create([
            'owner_id' => $landlord1->id,
            'owner_name' => $landlord1->name,
            'title' => 'Cozy Bedspace in Sampaloc',
            'description' => 'Clean and affordable bedspace near UST. Perfect for students. Includes free WiFi and electricity.',
            'category' => ListingCategory::SPACE_RENTAL,
            'type' => ListingType::BEDSPACE,
            'price' => 3500,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.6091,
            'lng' => 120.9894,
            'address' => 'España Blvd',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af',
                'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
            ],
            'amenities' => ['WiFi', 'Aircon', 'Laundry'],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.7,
            'rating_count' => 23,
        ]);

        Listing::create([
            'owner_id' => $landlord1->id,
            'owner_name' => $landlord1->name,
            'title' => 'Modern Boarding House - Quiapo',
            'description' => 'Fully furnished boarding house with 8 rooms. Security 24/7. Near LRT-1 Carriedo Station.',
            'category' => ListingCategory::SPACE_RENTAL,
            'type' => ListingType::BOARDING_HOUSE,
            'price' => 5000,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5995,
            'lng' => 120.9842,
            'address' => 'Quezon Blvd',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2',
            ],
            'amenities' => ['WiFi', 'Water', 'Security', 'Parking'],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.5,
            'rating_count' => 18,
        ]);

        Listing::create([
            'owner_id' => $landlord2->id,
            'owner_name' => $landlord2->name,
            'title' => 'Studio Apartment in Malate',
            'description' => '22 sqm studio unit with balcony. Walking distance to Robinsons Place Manila.',
            'category' => ListingCategory::SPACE_RENTAL,
            'type' => ListingType::APARTMENT,
            'price' => 12000,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5764,
            'lng' => 120.9960,
            'address' => 'Pedro Gil St',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1502672260066-6bc25f92a4a0',
                'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267',
            ],
            'amenities' => ['WiFi', 'Aircon', 'Kitchen', 'Balcony'],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::PENDING,
            'rating_average' => 4.3,
            'rating_count' => 7,
        ]);

        Listing::create([
            'owner_id' => $landlord2->id,
            'owner_name' => $landlord2->name,
            'title' => '2BR House for Rent - Sta. Mesa',
            'description' => 'Spacious 2-bedroom house with garage. Family-friendly neighborhood.',
            'category' => ListingCategory::SPACE_RENTAL,
            'type' => ListingType::HOUSE,
            'price' => 18000,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.6099,
            'lng' => 121.0157,
            'address' => 'Ramon Magsaysay Blvd',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9',
            ],
            'amenities' => ['WiFi', 'Water', 'Garage', 'Garden'],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::NONE,
            'rating_average' => 4.8,
            'rating_count' => 12,
        ]);

        Listing::create([
            'owner_id' => $landlord1->id,
            'owner_name' => $landlord1->name,
            'title' => 'Commercial Space - Binondo',
            'description' => '50 sqm commercial space on ground floor. High foot traffic area.',
            'category' => ListingCategory::SPACE_RENTAL,
            'type' => ListingType::COMMERCIAL,
            'price' => 35000,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5995,
            'lng' => 120.9736,
            'address' => 'Ongpin St',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1497366216548-37526070297c',
            ],
            'amenities' => ['Water', 'Electricity', 'Security'],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.4,
            'rating_count' => 5,
        ]);

        // Create a draft listing
        Listing::create([
            'owner_id' => $landlord2->id,
            'owner_name' => $landlord2->name,
            'title' => 'Bedspace in Ermita (Draft)',
            'description' => 'Still preparing details...',
            'category' => ListingCategory::SPACE_RENTAL,
            'type' => ListingType::BEDSPACE,
            'price' => 4000,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5833,
            'lng' => 120.9833,
            'address' => 'Mabini St',
            'city' => 'Manila',
            'photos' => [],
            'amenities' => [],
            'status' => ListingStatus::DRAFT,
            'property_verification' => VerificationStatus::NONE,
        ]);

        // ========================================
        // VEHICLE RENTALS
        // ========================================
        
        Listing::create([
            'owner_id' => $landlord1->id,
            'owner_name' => $landlord1->name,
            'title' => 'Toyota Hiace Van for Rent',
            'description' => '15-seater van perfect for family trips and group tours. Clean and well-maintained. With professional driver available.',
            'category' => ListingCategory::VEHICLE_RENTAL,
            'type' => ListingType::VAN,
            'price' => 3500,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5995,
            'lng' => 120.9842,
            'address' => 'Taft Avenue',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1527786356703-4b100091cd2c',
            ],
            'amenities' => ['Air Conditioning', 'GPS Navigation', 'Sound System'],
            'attributes' => [
                'capacity' => '15 passengers',
                'transmission' => 'Manual',
                'fuel_type' => 'Diesel',
                'year' => '2020',
                'driver_included' => true,
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.9,
            'rating_count' => 34,
        ]);

        Listing::create([
            'owner_id' => $landlord2->id,
            'owner_name' => $landlord2->name,
            'title' => 'Pickup Truck - Lipat Bahay',
            'description' => 'Closed van pickup truck for moving and delivery services. Reliable and affordable.',
            'category' => ListingCategory::VEHICLE_RENTAL,
            'type' => ListingType::TRUCK,
            'price' => 2500,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.6091,
            'lng' => 120.9894,
            'address' => 'España Blvd',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1519003722824-194d4455a60c',
            ],
            'amenities' => ['Covered Cargo', 'Tie-down Straps'],
            'attributes' => [
                'capacity' => '1 ton',
                'transmission' => 'Manual',
                'fuel_type' => 'Diesel',
                'year' => '2019',
                'cargo_dimensions' => '6ft x 4ft x 4ft',
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.6,
            'rating_count' => 28,
        ]);

        Listing::create([
            'owner_id' => $landlord1->id,
            'owner_name' => $landlord1->name,
            'title' => 'Honda Click 150i Scooter',
            'description' => 'Fuel-efficient motorcycle for daily commute. Perfect for Manila traffic!',
            'category' => ListingCategory::VEHICLE_RENTAL,
            'type' => ListingType::MOTORCYCLE,
            'price' => 500,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5764,
            'lng' => 120.9960,
            'address' => 'Pedro Gil St',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1558981806-ec527fa84c39',
            ],
            'amenities' => ['Helmet Included', 'Under-seat Storage'],
            'attributes' => [
                'capacity' => '2 passengers',
                'transmission' => 'Automatic',
                'fuel_type' => 'Gasoline',
                'year' => '2022',
                'engine' => '150cc',
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::PENDING,
            'rating_average' => 4.7,
            'rating_count' => 15,
        ]);

        // ========================================
        // SKILL SERVICES
        // ========================================

        Listing::create([
            'owner_id' => $tenant1->id,
            'owner_name' => $tenant1->name,
            'title' => 'Licensed Plumber - Emergency Services',
            'description' => 'Professional plumbing services for residential and commercial. Available 24/7 for emergencies. Free consultation.',
            'category' => ListingCategory::SKILL_SERVICE,
            'type' => ListingType::PLUMBING,
            'price' => 800,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.6099,
            'lng' => 121.0157,
            'address' => 'Ramon Magsaysay Blvd',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1607472586893-edb57bdc0e39',
            ],
            'amenities' => ['Licensed', '24/7 Available', 'Emergency Service'],
            'attributes' => [
                'experience_years' => 10,
                'certifications' => ['TESDA Certified', 'PTF Member'],
                'service_area' => 'Metro Manila',
                'emergency_available' => true,
                'specializations' => ['Pipe Repair', 'Leak Detection', 'Drain Cleaning', 'Water Heater'],
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.9,
            'rating_count' => 67,
        ]);

        Listing::create([
            'owner_id' => $tenant2->id,
            'owner_name' => $tenant2->name,
            'title' => 'Electrician - Residential & Commercial',
            'description' => 'Certified electrician with 8 years experience. Installation, repair, and maintenance services.',
            'category' => ListingCategory::SKILL_SERVICE,
            'type' => ListingType::ELECTRICAL,
            'price' => 750,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5995,
            'lng' => 120.9736,
            'address' => 'Ongpin St',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1621905251189-08b45d6a269e',
            ],
            'amenities' => ['Licensed', 'Insured', 'Same-day Service'],
            'attributes' => [
                'experience_years' => 8,
                'certifications' => ['TESDA NC II', 'MEP Certified'],
                'service_area' => 'Manila, Makati, Pasay',
                'emergency_available' => false,
                'specializations' => ['Wiring', 'Circuit Breakers', 'Lighting', 'Appliance Installation'],
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::VERIFIED,
            'rating_average' => 4.8,
            'rating_count' => 42,
        ]);

        Listing::create([
            'owner_id' => $landlord2->id,
            'owner_name' => $landlord2->name,
            'title' => 'Professional House Cleaning Service',
            'description' => 'Thorough and reliable cleaning services for homes and offices. Eco-friendly products available.',
            'category' => ListingCategory::SKILL_SERVICE,
            'type' => ListingType::CLEANING,
            'price' => 600,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.5833,
            'lng' => 120.9833,
            'address' => 'Mabini St',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1581578731548-c64695cc6952',
            ],
            'amenities' => ['Eco-friendly Products', 'Supplies Included', 'Background Checked'],
            'attributes' => [
                'experience_years' => 5,
                'certifications' => ['Housekeeping NC II'],
                'service_area' => 'Metro Manila',
                'team_size' => '2-3 persons',
                'services_offered' => ['General Cleaning', 'Deep Cleaning', 'Move-in/Move-out', 'Post-construction'],
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::PENDING,
            'rating_average' => 4.7,
            'rating_count' => 38,
        ]);

        Listing::create([
            'owner_id' => $tenant1->id,
            'owner_name' => $tenant1->name,
            'title' => 'Math & Science Tutor - High School',
            'description' => 'Experienced tutor for high school math and science subjects. Online and face-to-face sessions available.',
            'category' => ListingCategory::SKILL_SERVICE,
            'type' => ListingType::TUTORING,
            'price' => 500,
            'pricing_type' => 'fixed',
            'currency' => '₱',
            'lat' => 14.6091,
            'lng' => 120.9894,
            'address' => 'España Blvd',
            'city' => 'Manila',
            'photos' => [
                'https://images.unsplash.com/photo-1509062522246-3755977927d7',
            ],
            'amenities' => ['Online Sessions', 'Flexible Schedule', 'Free Materials'],
            'attributes' => [
                'experience_years' => 6,
                'education' => 'BS Mathematics, UP Diliman',
                'subjects' => ['Algebra', 'Trigonometry', 'Physics', 'Chemistry', 'Statistics'],
                'grade_levels' => ['Grade 7-12', 'College'],
                'session_duration' => '1-2 hours',
            ],
            'status' => ListingStatus::ACTIVE,
            'property_verification' => VerificationStatus::NONE,
            'rating_average' => 4.9,
            'rating_count' => 52,
        ]);

        // Get all listings for analytics seeding
        $listings = Listing::all();
        $activeListings = Listing::where('status', ListingStatus::ACTIVE)->get();

        // Create viewing requests
        ViewingRequest::create([
            'user_id' => $tenant1->id,
            'listing_id' => $activeListings[0]->id,
            'preferred_date' => now()->addDays(2),
            'message' => 'Hi! I would like to view this property this weekend.',
            'status' => ViewingStatus::PENDING,
        ]);

        ViewingRequest::create([
            'user_id' => $tenant1->id,
            'listing_id' => $activeListings[1]->id,
            'preferred_date' => now()->addDays(3),
            'message' => 'Interested in viewing. Available on weekdays.',
            'status' => ViewingStatus::CONFIRMED,
            'confirmed_at' => now()->subDay(),
        ]);

        ViewingRequest::create([
            'user_id' => $tenant2->id,
            'listing_id' => $activeListings[2]->id,
            'preferred_date' => now()->addDays(5),
            'message' => 'Can I schedule a viewing?',
            'status' => ViewingStatus::PENDING,
        ]);

        ViewingRequest::create([
            'user_id' => $tenant2->id,
            'listing_id' => $activeListings[0]->id,
            'preferred_date' => now()->subDays(2),
            'message' => 'Urgent viewing needed.',
            'status' => ViewingStatus::COMPLETED,
            'confirmed_at' => now()->subDays(3),
            'completed_at' => now()->subDays(2),
        ]);

        // Create favorites
        Favorite::create([
            'user_id' => $tenant1->id,
            'listing_id' => $activeListings[0]->id,
        ]);

        Favorite::create([
            'user_id' => $tenant1->id,
            'listing_id' => $activeListings[2]->id,
        ]);

        Favorite::create([
            'user_id' => $tenant2->id,
            'listing_id' => $activeListings[1]->id,
        ]);

        Favorite::create([
            'user_id' => $tenant2->id,
            'listing_id' => $activeListings[3]->id,
        ]);

        // Create listing views
        foreach ($activeListings as $listing) {
            // Random views from different users
            $viewCount = rand(5, 25);
            for ($i = 0; $i < $viewCount; $i++) {
                ListingView::create([
                    'listing_id' => $listing->id,
                    'user_id' => rand(0, 1) ? $tenant1->id : $tenant2->id,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('Aadi test data seeded successfully!');
        $this->command->info('Test accounts:');
        $this->command->info('Tenant 1: maria@test.com / password');
        $this->command->info('Tenant 2: juan@test.com / password');
        $this->command->info('Landlord 1: pedro@test.com / password');
        $this->command->info('Landlord 2: ana@test.com / password');
        $this->command->info('Pending Landlord: carlos@test.com / password');
    }
}
