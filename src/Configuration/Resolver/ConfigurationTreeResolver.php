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

namespace Gally\Configuration\Resolver;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use Gally\Configuration\Entity\ConfigurationTree;
use Gally\Configuration\Service\ConfigurationTreeBuilder;

class ConfigurationTreeResolver implements QueryItemResolverInterface
{
    public function __construct(private ConfigurationTreeBuilder $builder)
    {
    }

    public function __invoke(?object $item, array $context): ConfigurationTree
    {
        return $this->builder->build();
    }
}
