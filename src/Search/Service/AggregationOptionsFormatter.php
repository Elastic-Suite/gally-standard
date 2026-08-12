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

namespace Gally\Search\Service;

use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Configuration\Service\ConfigurationManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceField\Type;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Adapter\Common\Response\BucketValueInterface;
use Gally\Search\Elasticsearch\Builder\Response\AggregationBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the generic options list from raw aggregation bucket values.
 * Shared by every AggregationProviderInterface::formatAggregationOptions() implementation so the
 * building logic stays consistent regardless of which provider is configured.
 */
class AggregationOptionsFormatter
{
    public function __construct(
        private CategoryConfigurationRepository $categoryConfigurationRepository,
        private ConfigurationManager $configurationManager,
        private TranslatorInterface $translator,
    ) {
    }

    public function format(
        AggregationInterface $aggregation,
        SourceField $sourceField,
        ContainerConfigurationInterface $containerConfig,
    ): array {
        $options = [];

        if (empty($aggregation->getValues())) {
            return $options;
        }

        $labels = [];
        if (Type::TYPE_CATEGORY === $sourceField->getType()) {
            $categoryIds = array_map(
                fn ($item) => $item->getKey(),
                array_filter($aggregation->getValues(), fn ($item) => $item->getCount())
            );
            $categories = $this->categoryConfigurationRepository->findBy(
                ['category' => $categoryIds, 'localizedCatalog' => $containerConfig->getLocalizedCatalog()]
            );
            array_walk(
                $categories,
                function ($categoryConfig) use (&$labels) {
                    $labels[$categoryConfig->getCategory()->getId()] = $categoryConfig->getName();
                }
            );
        }

        foreach ($aggregation->getValues() as $value) {
            if (!$value instanceof BucketValueInterface) {
                continue;
            }

            $key = $value->getKey();

            if (AggregationBuilder::OTHER_DOCS_KEY === $key) {
                continue;
            }

            if (0 === $value->getCount()) {
                continue;
            }

            if (Type::TYPE_LOCATION === $sourceField->getType()) {
                $code = $key;
                $label = $this->getDistanceRangeLabel($key, $containerConfig);
            } elseif (\is_array($key)) {
                $code = $key[1];
                $label = 'None' !== $key[0] ? $key[0] : $key[1];
            } else {
                $code = $key;
                $label = $labels[$key] ?? $key;
            }

            $options[] = ['count' => $value->getCount(), 'value' => $code, 'label' => $label];
        }

        return $options;
    }

    private function getDistanceRangeLabel(string $key, ContainerConfigurationInterface $containerConfig): string
    {
        $range = explode('-', $key);
        $unit = $this->configurationManager->getScopedConfigValue('gally.search_settings.default_distance_unit');

        if ('*' === $range[0]) {
            return $this->translator->trans(
                'search.distance_facet.option_to.label',
                ['%distance' => $range[1], '%unit' => $unit],
                'gally_search',
                $containerConfig->getLocalizedCatalog()->getLocale()
            );
        }
        if ('*' === $range[1]) {
            return $this->translator->trans(
                'search.distance_facet.option_from.label',
                ['%distance' => $range[0], '%unit' => $unit],
                'gally_search',
                $containerConfig->getLocalizedCatalog()->getLocale()
            );
        }

        return $this->translator->trans(
            'search.distance_facet.option_fromto.label',
            ['%distanceFrom' => $range[0], '%distanceTo' => $range[1], '%unit' => $unit],
            'gally_search',
            $containerConfig->getLocalizedCatalog()->getLocale()
        );
    }
}
