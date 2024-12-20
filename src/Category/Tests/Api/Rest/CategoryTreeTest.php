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

namespace Gally\Category\Tests\Api\Rest;

use Gally\Catalog\Repository\CatalogRepository;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CategoryTreeTest extends CategoryTest
{
    public function testInvalidCatalog(): void
    {
        $this->getCategoryTree('b2c_it');
    }

    protected function getCategoryTree(?string $catalogCode = null, ?string $localizedCatalogCode = null): array
    {
        $expectedResponseCode = 200;
        $responseData = [];
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $catalogRepository = static::getContainer()->get(CatalogRepository::class);

        $params = [];
        if ($catalogCode) {
            $catalog = $catalogRepository->findOneBy(['code' => $catalogCode]);
            $params[] = 'catalogId=' . ($catalog ? $catalog->getId() : '999');
            $expectedResponseCode = $catalog ? 200 : 404;
        }
        if ($localizedCatalogCode) {
            $localizedCatalog = $localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode]);
            $params[] = 'localizedCatalogId=' . ($localizedCatalog ? $localizedCatalog->getId() : '999');
            $expectedResponseCode = $localizedCatalog ? 200 : 404;
        }

        $query = !empty($params) ? '?' . implode('&', $params) : '';

        $this->validateApiCall(
            new RequestToTest('GET', "categoryTree$query", null),
            new ExpectedResponse(
                $expectedResponseCode,
                function (ResponseInterface $response) use (&$responseData) {
                    $responseData = $response->toArray()['categories'];
                }
            )
        );

        return $responseData;
    }
}
