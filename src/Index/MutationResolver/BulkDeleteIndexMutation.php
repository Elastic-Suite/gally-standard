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

namespace Gally\Index\MutationResolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use Gally\Index\Dto\Bulk;
use Gally\Index\Entity\Index;

class BulkDeleteIndexMutation extends BulkIndexMutation implements MutationResolverInterface
{
    /**
     * @param Index|null $item
     *
     * @return Index
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $index = $this->getIndex($context);
        $request = new Bulk\Request();
        $request->deleteDocuments($index, $context['args']['input']['ids'] ?? []);

        $this->runBulkQuery($index, $request);

        return $index;
    }
}
