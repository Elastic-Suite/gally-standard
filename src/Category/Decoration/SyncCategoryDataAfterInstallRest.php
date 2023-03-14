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

namespace Gally\Category\Decoration;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Gally\Category\Exception\SyncCategoryException;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Category\Service\CategorySynchronizer;
use Gally\Index\DataTransformer\InstallIndexDataTransformer;
use Gally\Index\Dto\InstallIndexInput;
use Gally\Index\Model\Index;

class SyncCategoryDataAfterInstallRest implements DataTransformerInterface
{
    public function __construct(
        private InstallIndexDataTransformer $decorated,
        private CategorySynchronizer $synchronizer,
        private CategoryProductPositionManager $categoryProductPositionManager,
    ) {
    }

    /**
     * @param InstallIndexInput $object  input object
     * @param string            $to      target class
     * @param array<mixed>      $context context
     *
     * @throws InvalidArgumentException
     *
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        /** @var Index $index */
        $index = $this->decorated->transform($object, $to, $context);

        if ('category' === $index->getEntityType()) { // Synchronize sql data for category entity
            try {
                $this->synchronizer->synchronize($index);
            } catch (SyncCategoryException) {
                // If sync failed, retry sync once, then log the error.
                $this->synchronizer->synchronize($index);
            }
        }

        if ('product' === $index->getEntityType()) {
            $this->categoryProductPositionManager->reindexPositionsByIndex($index);
        }

        return $index;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return $this->decorated->supportsTransformation($data, $to, $context);
    }
}
