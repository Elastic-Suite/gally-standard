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

namespace Gally\Search\Decoration\GraphQl;

use ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use Gally\Search\Entity\Document;

/**
 * Add aggregations info in serializer context.
 */
class AddAggregationsInContext implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated,
    ) {
    }

    public function create(string $resourceClass, Operation $operation, array $resolverContext, bool $normalization): array
    {
        $context = $this->decorated->create($resourceClass, $operation, $resolverContext, $normalization);
        if (Document::class === $resourceClass || is_subclass_of($resourceClass, Document::class)) {
            $context['need_aggregations'] = $resolverContext['info']->getFieldSelection()['aggregations'] ?? false;
        }

        return $context;
    }
}
