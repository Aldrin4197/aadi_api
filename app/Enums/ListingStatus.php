<?php

namespace App\Enums;

enum ListingStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case RESERVED = 'reserved';
    case RENTED = 'rented';
    case ARCHIVED = 'archived';
}
