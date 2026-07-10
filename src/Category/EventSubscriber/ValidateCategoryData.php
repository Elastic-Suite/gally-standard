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

namespace Gally\Category\EventSubscriber;

use Gally\Category\Exception\SyncCategoryException;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Event\BeforeBulkIndexEvent;
use Gally\Index\Event\BeforeInstallIndexEvent;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Request\Query\Boolean as BooleanQuery;
use Gally\Search\Elasticsearch\Request\Query\Exists;
use Gally\Search\Elasticsearch\Request\Query\Not;
use Gally\Search\Elasticsearch\Request\Query\Term;
use Gally\Search\Elasticsearch\RequestFactoryInterface;
use Gally\Search\Entity\Document;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates category data (no null name, single root category per catalog) before it can ever
 * become visible through the API:
 * - once for the whole index content, before it is switched to its live alias ;
 * - once for every bulk applied afterwards to an already installed (ie. already live) index,
 *   this time counting the root categories already live for the catalog alongside the incoming
 *   bulk data, since a bulk only carries a partial diff of the category tree.
 */
class ValidateCategoryData implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly Adapter $adapter,
        private readonly IndexSettingsInterface $indexSettings,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeInstallIndexEvent::NAME => 'onBeforeInstallIndex',
            BeforeBulkIndexEvent::NAME => 'onBeforeBulkIndex',
        ];
    }

    public function onBeforeInstallIndex(BeforeInstallIndexEvent $event): void
    {
        $index = $event->getIndex();

        if ('category' !== $index->getEntityType()) {
            return;
        }

        $categoryData = array_map(
            fn (Document $document) => $document->getSource(),
            array_values($this->getInvalidCategories($index))
        );

        $this->validate($index, $categoryData);
    }

    public function onBeforeBulkIndex(BeforeBulkIndexEvent $event): void
    {
        $index = $event->getIndex();

        if ('category' !== $index->getEntityType() || !$this->indexSettings->isInstalled($index)) {
            return;
        }

        $existingCategoryData = array_map(
            fn (Document $document) => $document->getSource(),
            $this->getInvalidCategories($index)
        );

        // The bulk only carries a partial diff of the category tree: merge it over the currently
        // live root/nameless categories, the bulk data taking precedence for ids it touches.
        $this->validate($index, array_replace($existingCategoryData, $event->getData()));
    }

    /**
     * @param array[] $categoryData list of raw category data (each entry having 'id', 'name', 'parentId' keys), keyed by id
     *
     * @throws SyncCategoryException
     */
    private function validate(Index $index, array $categoryData): void
    {
        $rootCategoryIds = [];

        foreach ($categoryData as $data) {
            if (empty($data['name'])) {
                throw new SyncCategoryException(\sprintf('No name provided for category %s', $data['id'] ?? '?'));
            }

            $id = $data['id'] ?? '?';
            if (!empty($data['parentId'])) {
                unset($rootCategoryIds[$id]); // No longer (or wasn't) a root category.
            } else {
                $rootCategoryIds[$id] = true; // Is (now) a root category.
            }
        }

        if (\count($rootCategoryIds) > 1) {
            throw new SyncCategoryException(\sprintf('Catalog "%s" cannot have more than one root category (found: %s).', $index->getLocalizedCatalog()?->getCatalog()?->getCode() ?? '?', implode(', ', array_keys($rootCategoryIds))));
        }
    }

    /**
     * Fetch, directly from the index, only the categories missing a name or a parentId: these
     * are the only ones that can make validate() fail, no need to load the whole category tree.
     * If this ever returns 100 categories, something is already very wrong with the data.
     *
     * @return Document[]
     */
    private function getInvalidCategories(Index $index): array
    {
        $query = new BooleanQuery(
            should: [
                new Not(query: new Exists(field: 'name')),
                new Term(value: '', field: 'name'),
                new Not(query: new Exists(field: 'parentId')),
                new Term(value: '', field: 'parentId'),
            ],
            minimumShouldMatch: 1,
        );

        $request = $this->requestFactory->create([
            'name' => 'validate_category_data',
            'indexName' => $index->getName(),
            'query' => $query,
            'from' => 0,
            'size' => 100,
        ]);

        $data = iterator_to_array($this->adapter->search($request));

        $invalidCategories = [];
        array_walk(
            $data,
            function (Document $category) use (&$invalidCategories) {
                $invalidCategories[$category->getId()] = $category;
            }
        );

        return $invalidCategories;
    }
}
