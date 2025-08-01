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

namespace Gally;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Gally\Search\Compiler\GetContainerConfigurationFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
class GallyBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $mappings = [
            realpath(__DIR__ . '/Catalog/Resources/config/doctrine') => 'Gally\Catalog\Entity',
            realpath(__DIR__ . '/Category/Resources/config/doctrine') => 'Gally\Category\Entity',
            realpath(__DIR__ . '/Configuration/Resources/config/doctrine') => 'Gally\Configuration\Entity',
            realpath(__DIR__ . '/Metadata/Resources/config/doctrine') => 'Gally\Metadata\Entity',
            realpath(__DIR__ . '/User/Resources/config/doctrine') => 'Gally\User\Entity',
            realpath(__DIR__ . '/Search/Resources/config/doctrine') => 'Gally\Search\Entity',
        ];

        if ('test' === $container->getParameter('kernel.environment')) {
            $mappings[realpath(__DIR__ . '/Catalog/Resources/config/test/doctrine')] = 'Gally\Catalog\Tests\Entity';
            $mappings[realpath(__DIR__ . '/Doctrine/Resources/config/test/doctrine')] = 'Gally\Doctrine\Tests\Entity';
        }

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver(
                $mappings,
                ['doctrine.orm.entity_manager'],
                false
            )
        );
        $container->addCompilerPass(new GetContainerConfigurationFactory());
    }
}
