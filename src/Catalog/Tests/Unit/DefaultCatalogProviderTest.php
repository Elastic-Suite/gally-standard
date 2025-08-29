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

use Gally\Catalog\Exception\NoCatalogException;
use Gally\Catalog\Service\DefaultCatalogProvider;
use Gally\Test\AbstractTestCase;

class DefaultCatalogProviderTest extends AbstractTestCase
{
    protected DefaultCatalogProvider $defaultCatalogProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultCatalogProvider = static::getContainer()->get('Gally\Catalog\Service\DefaultCatalogProviderTest'); // @phpstan-ignore-line
    }

    public function testNoLocalizedCatalog(): void
    {
        $this->loadFixture([__DIR__ . '/../fixtures/catalogs.yaml']);
        $this->expectException(NoCatalogException::class);
        $this->defaultCatalogProvider->getDefaultLocalizedCatalog();
    }

    public function testGetLocalizedCatalog(): void
    {
        $this->loadFixture([__DIR__ . '/../fixtures/catalogs.yaml', __DIR__ . '/../fixtures/localized_catalogs.yaml']);
        $catalog = $this->defaultCatalogProvider->getDefaultLocalizedCatalog();
        $this->assertEquals('B2C French Store View', $catalog->getName());
    }

    public function testGetLocalizedCatalogWithDefault(): void
    {
        $this->loadFixture([__DIR__ . '/../fixtures/catalogs.yaml', __DIR__ . '/../fixtures/localized_catalogs_with_default.yaml']);
        $catalog = $this->defaultCatalogProvider->getDefaultLocalizedCatalog();
        $this->assertEquals('B2B English Store View', $catalog->getName());
    }
}
