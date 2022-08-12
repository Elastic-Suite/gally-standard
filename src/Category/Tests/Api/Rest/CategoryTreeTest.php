<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2022 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Elasticsuite\Index\Tests\Api\Rest;

use Elasticsuite\Catalog\Repository\CatalogRepository;
use Elasticsuite\Catalog\Repository\LocalizedCatalogRepository;
use Elasticsuite\Standard\src\Test\ExpectedResponse;
use Elasticsuite\Standard\src\Test\RequestToTest;
use Elasticsuite\User\Constant\Role;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CategoryTreeTest extends \Elasticsuite\Index\Tests\Api\GraphQl\CategoryTreeTest
{
    protected function getCategoryTree(?string $catalogCode = null, ?string $localizedCatalogCode = null): array
    {
        $responseData = [];
        $localizedCatalogRepository = static::getContainer()->get(LocalizedCatalogRepository::class);
        $catalogRepository = static::getContainer()->get(CatalogRepository::class);

        $params = [];
        if ($catalogCode) {
            $params[] = 'catalogId='
                . $catalogRepository->findOneBy(['code' => $catalogCode])->getId();
        }
        if ($localizedCatalogCode) {
            $params[] = 'localizedCatalogId='
                . $localizedCatalogRepository->findOneBy(['code' => $localizedCatalogCode])->getId();
        }

        $query = !empty($params) ? '?' . implode('&', $params) : '';

        $this->validateApiCall(
            new RequestToTest('GET', "categoryTree$query", $this->getUser(Role::ROLE_ADMIN)),
            new ExpectedResponse(
                200,
                function (ResponseInterface $response) use (&$responseData) {
                    $responseData = $response->toArray()['categories'];
                }
            )
        );

        return $responseData;
    }
}
