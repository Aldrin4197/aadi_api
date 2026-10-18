<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case NONE = 'none';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
}
