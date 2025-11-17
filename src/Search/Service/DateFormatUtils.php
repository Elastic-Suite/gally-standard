<?php

/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\Service;

/**
 * Converts date format strings between Elasticsearch and PHP formats.
 */
class DateFormatUtils
{
    public const COMPLETE_DATE_FORMAT = 'yyyy-MM-dd';

    /**
     * Converts Elasticsearch date format to PHP date format.
     */
    public function esFormatToPhp(string $esFormat): string
    {
        $map = [
            'yyyy' => 'Y',
            'yy' => 'y',

            'MMMM' => 'F',
            'MMM' => 'M',
            'MM' => 'm',
            'M' => 'n',

            'dd' => 'd',
            'd' => 'j',

            'HH' => 'H',
            'H' => 'G',
            'hh' => 'h',
            'h' => 'g',

            'mm' => 'i',
            'ss' => 's',
            'SSS' => 'v',

            'Z' => 'O',
            'ZZ' => 'O',
            'XXX' => 'P',
            'XX' => 'O',
            'X' => 'O',
        ];

        // Replace starting with the longest tokens to avoid collisions.
        uksort($map, fn ($a, $b) => \strlen($b) <=> \strlen($a));

        return strtr($esFormat, $map);
    }

    /**
     * Creates a DateTime object from a date string using Elasticsearch format.
     */
    public function getDateObjectFromFormat(string $dateValue, string $esFormat): \DateTime|false
    {
        $phpDateFormat = $this->esFormatToPhp($esFormat);

        return \DateTime::createFromFormat('!' . $phpDateFormat, $dateValue);
    }

    /**
     * Formats a DateTime object using Elasticsearch format.
     */
    public function format(\DateTime $dateTime, string $esFormat): string
    {
        $phpDateFormat = $this->esFormatToPhp($esFormat);

        return $dateTime->format($phpDateFormat);
    }

    /**
     * Checks if a date string matches the expected Elasticsearch format.
     */
    public function checkDateFormat(string $dateValue, string $esFormat): bool
    {
        $dateTime = $this->getDateObjectFromFormat($dateValue, $esFormat);

        return $dateTime && $this->format($dateTime, $esFormat) === $dateValue;
    }

    /**
     * Gets the first day of a period based on the given date and format.
     */
    public function getFirstDayOfPeriod(string $period, string $esFormat): string
    {
        $date = $this->getDateObjectFromFormat($period, $esFormat);

        return $this->format($date, self::COMPLETE_DATE_FORMAT);
    }

    /**
     * Gets the last day of a period based on the given date and format.
     */
    public function getLastDayOfPeriod(string $period, string $esFormat): string
    {
        $date = $this->getDateObjectFromFormat($period, $esFormat);

        $phpDateFormat = $this->esFormatToPhp($esFormat);
        // Determine interval from the last char of the php date format.
        $interval = strtoupper(substr($phpDateFormat, -1));
        $date = $date->add(new \DateInterval('P1' . $interval))
            ->sub(new \DateInterval('P1D'));

        return $this->format($date, self::COMPLETE_DATE_FORMAT);
    }
}
