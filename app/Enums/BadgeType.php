<?php

namespace App\Enums;

enum BadgeType: string
{
    case EMAIL_VERIFIED = 'emailVerified';
    case PHONE_VERIFIED = 'phoneVerified';
    case ID_VERIFIED = 'idVerified';
    case LANDLORD_VERIFIED = 'landlordVerified';
    case PROPERTY_VERIFIED = 'propertyVerified';
    case RELIABLE_TENANT = 'reliableTenant';
    case RESPONSIVE = 'responsive';
}
