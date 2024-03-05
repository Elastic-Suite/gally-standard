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

namespace Gally\Analysis\Tests\Unit;

use Gally\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Gally's configuration with only analysis node.
 */
class ConfigurationMock extends Configuration
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE_CONFIG);

        /** @var ArrayNodeDefinition $gallyNodeRoot */
        $gallyNodeRoot = parent::getConfigTreeBuilder()->getRootNode();
        foreach ($gallyNodeRoot->getChildNodeDefinitions() as $name => $childNodeDefinition) {
            if ('analysis' === $name) {
                $treeBuilder->getRootNode()
                    ->children()
                    ->append($childNodeDefinition);
            }
        }

        return $treeBuilder;
    }
}
