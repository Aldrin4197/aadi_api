<?php

namespace App\Enums;

enum ListingCategory: string
{
    case SPACE_RENTAL = 'space_rental';
    case VEHICLE_RENTAL = 'vehicle_rental';
    case SKILL_SERVICE = 'skill_service';
    
    public function label(): string
    {
        return match($this) {
            self::SPACE_RENTAL => 'Space Rental',
            self::VEHICLE_RENTAL => 'Utility Vehicle Rental',
            self::SKILL_SERVICE => 'Skills Service',
        };
    }
}
