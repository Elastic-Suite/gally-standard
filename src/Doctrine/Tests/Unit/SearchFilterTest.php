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
            'd59474c1ebd88634a8e77781c32ea30271094675',
            sha1_file('vendor/api-platform/core/src/Doctrine/Orm/Filter/SearchFilter.php'),
            'The original \ApiPlatform\Doctrine\Orm\Filter\SearchFilter file has been updated, please backport this changes in \Gally\Doctrine\Filter\SearchFilter'
        );
    }
}
