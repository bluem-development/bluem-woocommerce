<?php

namespace Bluem\Wordpress\Presentation;

use DateTime;
use DateTimeZone;
use Exception;

final class BluemDateFormatter
{
    public function __construct(private readonly string $timezone = 'Europe/Amsterdam')
    {
    }

    public function format(string $timestamp, string $format = 'd-m-Y H:i:s'): string
    {
        try {
            $dateTime = new DateTime($timestamp);
        } catch (Exception $exception) {
            return '';
        }

        $dateTime->setTimezone(new DateTimeZone($this->timezone));

        return $dateTime->format($format);
    }
}
