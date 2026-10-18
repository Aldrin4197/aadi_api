<?php

namespace App\Enums;

enum ListingType: string
{
    // Space Rental Types
    case BEDSPACE = 'bedspace';
    case BOARDING_HOUSE = 'boardingHouse';
    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case COMMERCIAL = 'commercial';
    
    // Vehicle Rental Types
    case VAN = 'van';
    case TRUCK = 'truck';
    case SUV = 'suv';
    case MOTORCYCLE = 'motorcycle';
    case BICYCLE = 'bicycle';
    
    // Skill Service Types
    case PLUMBING = 'plumbing';
    case ELECTRICAL = 'electrical';
    case CARPENTRY = 'carpentry';
    case CLEANING = 'cleaning';
    case TUTORING = 'tutoring';
    case IT_SUPPORT = 'itSupport';
    case MOVING = 'moving';
    case APPLIANCE_REPAIR = 'applianceRepair';
    
    public function label(): string
    {
        return match($this) {
            // Space Rentals
            self::BEDSPACE => 'Bedspace',
            self::BOARDING_HOUSE => 'Boarding House',
            self::APARTMENT => 'Apartment',
            self::HOUSE => 'House',
            self::COMMERCIAL => 'Commercial',
            
            // Vehicle Rentals
            self::VAN => 'Van',
            self::TRUCK => 'Truck',
            self::SUV => 'SUV',
            self::MOTORCYCLE => 'Motorcycle',
            self::BICYCLE => 'Bicycle',
            
            // Skill Services
            self::PLUMBING => 'Plumbing',
            self::ELECTRICAL => 'Electrical',
            self::CARPENTRY => 'Carpentry',
            self::CLEANING => 'Cleaning',
            self::TUTORING => 'Tutoring',
            self::IT_SUPPORT => 'IT Support',
            self::MOVING => 'Moving',
            self::APPLIANCE_REPAIR => 'Appliance Repair',
        };
    }
    
    public function category(): ListingCategory
    {
        return match($this) {
            self::BEDSPACE, self::BOARDING_HOUSE, self::APARTMENT, self::HOUSE, self::COMMERCIAL 
                => ListingCategory::SPACE_RENTAL,
            
            self::VAN, self::TRUCK, self::SUV, self::MOTORCYCLE, self::BICYCLE 
                => ListingCategory::VEHICLE_RENTAL,
            
            self::PLUMBING, self::ELECTRICAL, self::CARPENTRY, self::CLEANING, 
            self::TUTORING, self::IT_SUPPORT, self::MOVING, self::APPLIANCE_REPAIR 
                => ListingCategory::SKILL_SERVICE,
        };
    }
}
