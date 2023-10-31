<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Search\Model\Source;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\Metadata\Model\SourceField;
use Gally\Search\Resolver\DummyResolver;

#[ApiResource(
    itemOperations: [],
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'openapi_context' => [
                'parameters' => [
                    [
                        'name' => 'entityType',
                        'in' => 'query',
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ],
        ],
    ],
    graphql: [
        'get' => [
            'collection_query' => DummyResolver::class,
            'pagination_enabled' => false,
            'args' => [
                'entityType' => ['type' => 'String'],
            ],
        ],
    ],
    attributes: [
        'gally' => [
            // Allows to add cache tag "/source_fields" in the HTTP response to invalidate proxy cache when a source field is saved.
            'cache_tag' => ['resource_classes' => [SourceField::class]],
        ],
    ],
)]
class SortingOption
{
    #[ApiProperty(identifier: true)]
    public string $code;

    public string $label;
}
