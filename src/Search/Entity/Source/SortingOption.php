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

namespace Gally\Search\Entity\Source;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\OpenApi\Model;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Resolver\DummyResolver;
use Gally\Search\State\SortingOptionProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            paginationEnabled: false,
            openapi: new Model\Operation(
                parameters: [
                    new Model\Parameter(
                        name: 'entityType',
                        in: 'query',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                ],
            )
        ),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'get',
            resolver: DummyResolver::class,
            paginationEnabled: false,
            args: ['entityType' => ['type' => 'String']]),
    ],
    provider: SortingOptionProvider::class,
    extraProperties: [
        'gally' => ['cache_tag' => ['resource_classes' => [SourceField::class]]],
    ]
)]
class SortingOption
{
    #[ApiProperty(identifier: true)]
    public string $code;

    public string $label;

    public string $type;
}
