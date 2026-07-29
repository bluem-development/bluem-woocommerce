<?php

namespace Bluem\Wordpress\Identity;

/**
 * Calculates an age from a Bluem iDIN birthdate value.
 *
 * The plugin historically calculated age using elapsed 365-day periods. Keep
 * that behavior here while moving the calculation out of the WordPress hook
 * layer; calendar-accurate age can be considered as a separate behavior change.
 */
final class BluemAgeCalculator
{
    public function calculate($birthday, ?int $nowTimestamp = null): int
    {
        $birthdateTimestamp = strtotime($birthday);
        $nowTimestamp ??= time();

        return (int) floor(($nowTimestamp - $birthdateTimestamp) / 60 / 60 / 24 / 365);
    }
}
