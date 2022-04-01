<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @package   Elasticsuite
 * @author    ElasticSuite Team <elasticsuite@smile.fr>
 * @copyright 2022 Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Elasticsuite\Index\MutationResolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use Elasticsuite\Catalog\Repository\LocalizedCatalogRepository;
use Elasticsuite\Index\Api\IndexSettingsInterface;
use Elasticsuite\Index\Repository\Index\IndexRepositoryInterface;
use Elasticsuite\Index\Repository\Metadata\MetadataRepository;
use Elasticsuite\Index\Service\IndexManager;

class CreateIndexMutation implements MutationResolverInterface
{
    public function __construct(
        private IndexRepositoryInterface $indexRepository,
        private LocalizedCatalogRepository $catalogRepository,
        private MetadataRepository $metadataRepository,
        private IndexSettingsInterface $indexSettings,
        private IndexManager $indexManager
    ) {
    }

    /**
     * Handle mutation.
     *
     * @param object|null  $item    The item to be mutated
     * @param array<mixed> $context Context
     *
     * @throws \Exception
     *
     * @return object|null The mutated item
     */
    public function __invoke($item, array $context)
    {
        $entityType = $context['args']['input']['entityType'];
        $catalogId = $context['args']['input']['catalog'];

        $metadata = $this->metadataRepository->findOneBy(['entity' => $entityType]);
        if (!$metadata) {
            throw new \InvalidArgumentException(sprintf('Entity type [%s] does not exist', $entityType));
        }
        if (null === $metadata->getEntity()) {
            throw new \LogicException(sprintf('Entity type [%s] is not defined', $entityType));
        }

        $catalog = $this->catalogRepository->find($catalogId);
        if (!$catalog) {
            throw new \InvalidArgumentException(sprintf('Catalog of ID [%s] does not exist', $catalogId));
        }

        $indexSettings = [
            'settings' => $this->indexSettings->getCreateIndexSettings() + $this->indexSettings->getDynamicIndexSettings($catalog),
        ];
        $indexSettings['settings']['analysis'] = $this->indexSettings->getAnalysisSettings($catalog);
        $indexSettings['mappings'] = $this->indexManager->getMapping($metadata)->asArray();
        $newIndexAliases = $this->indexSettings->getNewIndexMetadataAliases($entityType, $catalog);
        if (!empty($newIndexAliases)) {
            $indexSettings['aliases'] = array_fill_keys($newIndexAliases, ['is_hidden' => true]);
        }

        try {
            $item = $this->indexRepository->create(
                $this->indexSettings->createIndexNameFromIdentifier(
                    $metadata->getEntity(),
                    $catalog
                ),
                $indexSettings
            );
        } catch (\Exception $exception) {
            // TODO log error
            throw new \Exception('An error occurred when creating the index');
        }

        return $item;
    }
}