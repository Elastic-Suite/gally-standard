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

namespace Gally\Search\Elasticsearch\Request\Container\RelevanceConfiguration;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Service\ConfigurationManager;

class RelevanceConfigurationFactory implements RelevanceConfigurationFactoryInterface
{
    public function __construct(
        protected ConfigurationManager $configurationManager,
    ) {
    }

    public function create(?LocalizedCatalog $localizedCatalog): RelevanceConfigurationInterface
    {
        $relevanceConfig = $this->configurationManager->getScopedConfigValues(
            'gally.relevance',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode(),
        );
        $fuzzinessConfiguration = new FuzzinessConfig(
            $relevanceConfig['fuzziness.value'],
            $relevanceConfig['fuzziness.prefixLength'],
            $relevanceConfig['fuzziness.maxExpansions'],
        );

        return new RelevanceConfiguration($relevanceConfig, $fuzzinessConfiguration);
    }
}
