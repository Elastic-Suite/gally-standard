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
use Gally\Configuration\Repository\ConfigurationRepository;
use Gally\Configuration\State\ConfigurationProvider;
use Gally\Search\Service\ScopableRequestTypeConfiguration;

class RelevanceConfigurationFactory extends ScopableRequestTypeConfiguration implements RelevanceConfigurationFactoryInterface
{
    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected array $relevanceConfig,
    ) {
    }

    public function create(?LocalizedCatalog $localizedCatalog, ?string $requestType): RelevanceConfigurationInterface
    {
        $relevanceConfig = $this->configurationRepository->getScopedConfigurations(
            'gally.relevance',
            [
                Configuration::SCOPE_LOCALIZED_CATALOG => $localizedCatalog,
                Configuration::SCOPE_REQUEST_TYPE => $requestType,
            ]
        );
        $fuzzinessConfiguration = new FuzzinessConfig(
            $relevanceConfig['gally.relevance.fuzziness.value']->getDecodedValue(),
            $relevanceConfig['gally.relevance.fuzziness.prefixLength']->getDecodedValue(),
            $relevanceConfig['gally.relevance.fuzziness.maxExpansions']->getDecodedValue(),
        );

        return new RelevanceConfiguration($relevanceConfig, $fuzzinessConfiguration);
    }
}
