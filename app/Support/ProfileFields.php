<?php

namespace App\Support;

class ProfileFields
{
    public const SEX_OPTIONS = ['female', 'male', 'prefer_not_to_say'];
    public const ADULT_MESSAGE = 'You must be at least 18 years old to register.';

    public static function birthDateRules(bool $adult = false): array
    {
        $latest = $adult ? today()->subYears(18)->toDateString() : today()->toDateString();

        return ['required', 'date_format:Y-m-d', 'before_or_equal:'.$latest];
    }
}
