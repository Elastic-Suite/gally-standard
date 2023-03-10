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

namespace Gally\Product\Decoration\GraphQl;

use ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface;
use Gally\Product\Model\Product;

/**
 * Add aggregations data in graphql search document response.
 */
class AddEntityTypeInContext implements SerializeStageInterface
{
    public function __construct(
        private SerializeStageInterface $decorated,
    ) {
    }

    public function __invoke($itemOrCollection, string $resourceClass, string $operationName, array $context): ?array
    {
        if (Product::class === $resourceClass) {
            $context['args']['entityType'] = 'product';
        }

        return $this->decorated->__invoke($itemOrCollection, $resourceClass, $operationName, $context);
    }
}
