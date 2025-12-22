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

namespace Gally\Doctrine\Tests\Unit;

use Gally\Test\AbstractTestCase;

class SearchFilterTest extends AbstractTestCase
{
    public function testOriginalFileHasChanged(): void
    {
        $this->assertEquals(
            '701a0e2aad40f659aff3c134d89d0feb3b685c1b',
            sha1_file('vendor/api-platform/doctrine-orm/Filter/SearchFilter.php'),
            'The original \ApiPlatform\Doctrine\Orm\Filter\SearchFilter file has been updated, please backport this changes in \Gally\Doctrine\Filter\SearchFilter'
        );
    }
}
