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

namespace Gally\Search\Tests\Unit\Service;

use Gally\Search\Service\DateFormatUtils;
use Gally\Test\AbstractTestCase;

class DateFormatUtilsTest extends AbstractTestCase
{
    private DateFormatUtils $dateFormatUtils;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dateFormatUtils = new DateFormatUtils();
    }

    /**
     * @dataProvider checkDateFormatDataProvider
     */
    public function testCheckDateFormat(string $value, string $format, bool $expected): void
    {
        $result = $this->dateFormatUtils->checkDateFormat($value, $format);
        $this->assertEquals($expected, $result);
    }

    protected function checkDateFormatDataProvider(): iterable
    {
        // Valid complete date format (yyyy-MM-dd)
        yield ['2023-01-15', DateFormatUtils::COMPLETE_DATE_FORMAT, true];
        yield ['2022-12-31', DateFormatUtils::COMPLETE_DATE_FORMAT, true];
        yield ['2021-02-28', DateFormatUtils::COMPLETE_DATE_FORMAT, true];

        // Invalid complete date format
        yield ['2023-13-15', DateFormatUtils::COMPLETE_DATE_FORMAT, false]; // Invalid month
        yield ['2023-01-32', DateFormatUtils::COMPLETE_DATE_FORMAT, false]; // Invalid day
        yield ['2023-02-29', DateFormatUtils::COMPLETE_DATE_FORMAT, false]; // Invalid leap year
        yield ['23-01-15', DateFormatUtils::COMPLETE_DATE_FORMAT, false];   // Wrong year format
        yield ['2023-1-15', DateFormatUtils::COMPLETE_DATE_FORMAT, false];  // Wrong month format
        yield ['2023-01-5', DateFormatUtils::COMPLETE_DATE_FORMAT, false];  // Wrong day format
        yield ['invalid-date', DateFormatUtils::COMPLETE_DATE_FORMAT, false];
        yield ['', DateFormatUtils::COMPLETE_DATE_FORMAT, false];

        // Valid year-month format (yyyy-MM)
        yield ['2023-01', 'yyyy-MM', true];
        yield ['2022-12', 'yyyy-MM', true];
        yield ['2021-02', 'yyyy-MM', true];

        // Invalid year-month format
        yield ['2023-13', 'yyyy-MM', false]; // Invalid month
        yield ['23-01', 'yyyy-MM', false];   // Wrong year format
        yield ['2023-1', 'yyyy-MM', false];  // Wrong month format
        yield ['2023', 'yyyy-MM', false];    // Missing month
        yield ['invalid', 'yyyy-MM', false];

        // Valid year format (yyyy)
        yield ['2023', 'yyyy', true];
        yield ['2022', 'yyyy', true];
        yield ['1999', 'yyyy', true];

        // Invalid year format
        yield ['23', 'yyyy', false];     // Wrong year format
        yield ['20233', 'yyyy', false];  // Too many digits
        yield ['abcd', 'yyyy', false];   // Non-numeric
        yield ['', 'yyyy', false];       // Empty

        // Edge cases
        yield ['2020-02-29', DateFormatUtils::COMPLETE_DATE_FORMAT, true]; // Valid leap year
        yield ['1900-02-29', DateFormatUtils::COMPLETE_DATE_FORMAT, false]; // Invalid leap year (1900 is not a leap year)
        yield ['2000-02-29', DateFormatUtils::COMPLETE_DATE_FORMAT, true]; // Valid leap year (2000 is a leap year)
    }

    /**
     * @dataProvider getFirstDayOfPeriodDataProvider
     */
    public function testGetFirstDayOfPeriod(string $value, string $format, string $expected): void
    {
        $result = $this->dateFormatUtils->getFirstDayOfPeriod($value, $format);
        $this->assertEquals($expected, $result);
    }

    protected function getFirstDayOfPeriodDataProvider(): iterable
    {
        // Year format
        yield ['2023', 'yyyy', '2023-01-01'];
        yield ['2022', 'yyyy', '2022-01-01'];
        yield ['1999', 'yyyy', '1999-01-01'];

        // Year-month format
        yield ['2023-01', 'yyyy-MM', '2023-01-01'];
        yield ['2023-12', 'yyyy-MM', '2023-12-01'];
        yield ['2022-06', 'yyyy-MM', '2022-06-01'];

        // Complete date format (should return the same date)
        yield ['2023-01-15', DateFormatUtils::COMPLETE_DATE_FORMAT, '2023-01-15'];
        yield ['2022-12-31', DateFormatUtils::COMPLETE_DATE_FORMAT, '2022-12-31'];
    }

    /**
     * @dataProvider getLastDayOfPeriodDataProvider
     */
    public function testGetLastDayOfPeriod(string $value, string $format, string $expected): void
    {
        $result = $this->dateFormatUtils->getLastDayOfPeriod($value, $format);
        $this->assertEquals($expected, $result);
    }

    protected function getLastDayOfPeriodDataProvider(): iterable
    {
        // Year format
        yield ['2023', 'yyyy', '2023-12-31'];
        yield ['2022', 'yyyy', '2022-12-31'];
        yield ['1999', 'yyyy', '1999-12-31'];

        // Year-month format
        yield ['2023-01', 'yyyy-MM', '2023-01-31'];
        yield ['2023-02', 'yyyy-MM', '2023-02-28']; // Non-leap year
        yield ['2020-02', 'yyyy-MM', '2020-02-29']; // Leap year
        yield ['2023-04', 'yyyy-MM', '2023-04-30']; // April has 30 days
        yield ['2023-12', 'yyyy-MM', '2023-12-31'];
        yield ['2022-06', 'yyyy-MM', '2022-06-30'];

        // Complete date format (should return the same date)
        yield ['2023-01-15', DateFormatUtils::COMPLETE_DATE_FORMAT, '2023-01-15'];
        yield ['2022-12-31', DateFormatUtils::COMPLETE_DATE_FORMAT, '2022-12-31'];
    }

    /**
     * @dataProvider convertElasticsearchToPhpFormatDataProvider
     */
    public function testConvertElasticsearchToPhpFormat(string $elasticsearchFormat, string $expected): void
    {
        $result = $this->dateFormatUtils->esFormatToPhp($elasticsearchFormat);
        $this->assertEquals($expected, $result);
    }

    protected function convertElasticsearchToPhpFormatDataProvider(): iterable
    {
        // Basic conversions
        yield ['yyyy', 'Y'];
        yield ['MM', 'm'];
        yield ['dd', 'd'];
        yield ['HH', 'H'];
        yield ['mm', 'i'];
        yield ['ss', 's'];

        // Combined formats
        yield ['yyyy-MM-dd', 'Y-m-d'];
        yield ['yyyy-MM', 'Y-m'];
        yield ['yyyy-MM-dd HH:mm:ss', 'Y-m-d H:i:s'];
        yield ['dd/MM/yyyy', 'd/m/Y'];
        yield ['MM/dd/yyyy', 'm/d/Y'];

        // Edge cases
        yield ['', ''];
        yield ['yyyy-MM-ddTHH:mm:ss', 'Y-m-dTH:i:s'];
        yield ['yyyy-MM-ddTHH:mm:ss.SSSZ', 'Y-m-dTH:i:s.vO'];
    }
}
