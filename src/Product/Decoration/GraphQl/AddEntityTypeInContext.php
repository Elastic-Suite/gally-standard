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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Product\Model\Product;

/**
 * Add aggregations data in graphql search document response.
 */
class AddEntityTypeInContext implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $decorated,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        if (Product::class === $operation->getClass()) {
            $context['args']['entityType'] = 'product';
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
