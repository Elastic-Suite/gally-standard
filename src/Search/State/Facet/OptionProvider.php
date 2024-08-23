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

namespace Gally\Search\State\Facet;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Category\Repository\CategoryConfigurationRepository;
use Gally\Category\Service\CurrentCategoryProvider;
use Gally\Metadata\Model\SourceField\Type;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Service\PriceGroupProvider;
use Gally\Metadata\Service\ReferenceLocationProvider;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\Model\Facet\Option;
use Gally\Search\Service\GraphQl\FilterManager;
use Gally\Search\Service\ReverseSourceFieldProvider;
use Gally\Search\Service\SearchContext;
use Gally\Search\Service\ViewMoreContext;

class OptionProvider implements ProviderInterface
{
    public function __construct(
        protected MetadataRepository $metadataRepository,
        protected LocalizedCatalogRepository $catalogRepository,
        protected ContainerConfigurationProvider $containerConfigurationProvider,
        protected SimpleRequestBuilder $requestBuilder,
        protected Adapter $searchEngine,
        protected FilterManager $filterManager,
        protected ViewMoreContext $viewMoreContext,
        protected ReverseSourceFieldProvider $reverseSourceFieldProvider,
        protected CategoryConfigurationRepository $categoryConfigurationRepository,
        protected CurrentCategoryProvider $currentCategoryProvider,
        protected PriceGroupProvider $priceGroupProvider,
        protected ReferenceLocationProvider $referenceLocationProvider,
        protected SearchContext $searchContext,
        protected ProviderInterface $itemProvider,
        protected string $nestingSeparator,
    ) {
    }

    /**
     * @return T|PartialPaginatorInterface<T>|iterable<T>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $this->itemProvider->provide($operation, $uriVariables, $context);
        }

        $searchQuery = $context['filters']['search'] ?? null;
        $this->initSearchContext($searchQuery);

        $metadata = $this->metadataRepository->findByEntity($context['filters']['entityType']);
        $localizedCatalog = $this->catalogRepository->findByCodeOrId($context['filters']['localizedCatalog']);
        $filterName = str_replace($this->nestingSeparator, '.', $context['filters']['aggregation']);
        $sourceField = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName($filterName, $metadata);
        if (null === $sourceField) {
            throw new \InvalidArgumentException("The source field '$filterName' does not exist");
        }

        $this->viewMoreContext->setFilterName($filterName);
        $this->viewMoreContext->setSourceField($sourceField);

        $containerConfig = $this->containerConfigurationProvider->get($metadata, $localizedCatalog);

        $this->filterManager->validateFilters($context, $containerConfig);

        // Get query filter and set current category.
        $queryFilter = $this->filterManager->transformToGallyFilters(
            $this->filterManager->getQueryFilterFromContext($context),
            $containerConfig
        );

        $request = $this->requestBuilder->create(
            $containerConfig,
            0,
            0,
            $searchQuery,
            [],
            $this->filterManager->transformToGallyFilters(
                $this->filterManager->getFiltersFromContext($context),
                $containerConfig
            ),
            $queryFilter,
            []
        );
        $response = $this->searchEngine->search($request);

        $options = [];

        if (\array_key_exists($filterName, $response->getAggregations())) {
            $labels = [];
            if (Type::TYPE_CATEGORY === $sourceField->getType()) {
                // Extract categories ids from aggregations options (with result) to hydrate labels from DB
                $categoryIds = array_map(
                    fn ($item) => $item->getKey(),
                    array_filter($response->getAggregations()[$filterName]->getValues(), fn ($item) => $item->getCount())
                );
                $categories = $this->categoryConfigurationRepository->findBy(
                    ['category' => $categoryIds, 'localizedCatalog' => $containerConfig->getLocalizedCatalog()]
                );
                // Get the name of all categories in aggregation result
                array_walk(
                    $categories,
                    function ($categoryConfig) use (&$labels) {
                        $labels[$categoryConfig->getCategory()->getId()] = $categoryConfig->getName();
                    }
                );
            }

            /** @var Adapter\Common\Response\BucketValueInterface $option */
            foreach ($response->getAggregations()[$filterName]->getValues() as $option) {
                if (0 === $option->getCount()) {
                    continue;
                }

                $options[] = \is_array($option->getKey())
                    ? new Option((string) $option->getKey()[1], (string) $option->getKey()[0], $option->getCount())
                    : new Option((string) $option->getKey(), $labels[$option->getKey()] ?? (string) $option->getKey(), $option->getCount());
            }
        }

        return $options;
    }

    protected function initSearchContext(?string $searchQuery): void
    {
        $this->searchContext->setCategory($this->currentCategoryProvider->getCurrentCategory());
        $this->searchContext->setSearchQueryText($searchQuery);
        $this->searchContext->setPriceGroup($this->priceGroupProvider->getCurrentPriceGroupId());
        $this->searchContext->setReferenceLocation($this->referenceLocationProvider->getReferenceLocation());
    }
}
