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

namespace Gally\Catalog\Tests\Unit;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Catalog\Filter\LocalizedCatalogLocaleFilter;
use Gally\Catalog\Tests\Entity\FakeEntity;
use Gally\Test\AbstractTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class LocalizedCatalogLocaleFilterTest extends AbstractTestCase
{
    public function testApply(): void
    {
        $manager = static::getContainer()->get(ManagerRegistry::class);
        $ressourceClass = FakeEntity::class;

        $filter = new LocalizedCatalogLocaleFilter(
            $manager,
            static::getContainer()->get(IriConverterInterface::class),
            static::getContainer()->get(PropertyAccessorInterface::class),
            null,
            ['localizedCatalogs.id' => ['locale_property' => 'locales.local']],
            static::getContainer()->get(IdentifiersExtractorInterface::class),
        );

        $queryBuilder = $manager->getRepository($ressourceClass)->createQueryBuilder('o');

        $filter->apply(
            $queryBuilder,
            new QueryNameGenerator(),
            $ressourceClass,
            null,
            ['filters' => ['localizedCatalogs.id' => ['fr_FR']]]
        );

        $this->assertEquals(
            'SELECT o FROM Gally\Catalog\Tests\Entity\FakeEntity o LEFT JOIN o.localizedCatalogs localizedCatalogs_a1 LEFT JOIN o.locales locales_a2 LEFT JOIN Gally\Catalog\Entity\LocalizedCatalog lcl WITH lcl.locale = locales_a2.local WHERE lcl.id IN(:localizedCatalogs) OR localizedCatalogs_a1.id IN(:localizedCatalogs)',
            $queryBuilder->getQuery()->getDQL()
        );
    }
}
