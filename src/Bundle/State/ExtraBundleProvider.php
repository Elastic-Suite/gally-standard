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

namespace Gally\Bundle\State;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Gally\Bundle\Model\ExtraBundle;
use Symfony\Component\HttpKernel\KernelInterface;
use ApiPlatform\State\ProviderInterface;


class ExtraBundleProvider implements ProviderInterface
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @return T|PartialPaginatorInterface<T>|iterable<T>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $extraBundles = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if (str_starts_with($bundle->getName(), ExtraBundle::GALLY_BUNDLE_PREFIX) && ExtraBundle::GALLY_STANDARD_BUNDLE != $bundle->getName()) {
                $extraBundles[] = ['id' => $bundle->getName(), 'name' => $bundle->getName()];
            }
        }

        return $extraBundles;
    }
}
