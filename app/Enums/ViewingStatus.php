<?php

namespace App\Enums;

enum ViewingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case DECLINED = 'declined';
    case RESCHEDULED = 'rescheduled';
}
