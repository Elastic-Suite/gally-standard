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
            '82d4d86b6bec37d045f2cd602af9f3d4dfff325d',
            sha1_file('vendor/api-platform/core/src/Doctrine/Odm/Filter/SearchFilter.php'),
            'The original \ApiPlatform\Doctrine\Odm\Filter\SearchFilter file has been updated, please backport this changes in \Gally\Doctrine\Filter\SearchFilter'
        );
    }
}
