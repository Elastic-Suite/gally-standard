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

namespace Gally\Index\Service;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Exception\LogicException;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Model\Index;
use Gally\Index\Model\Index\Mapping\FieldInterface;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Metadata\Model\Metadata;

class IndexOperation
{
    public function __construct(
        protected IndexRepositoryInterface $indexRepository,
        protected IndexSettingsInterface $indexSettings,
        protected MetadataManager $metadataManager
    ) {
    }

    /**
     * Creates an index for a given entity metadata and catalog.
     *
     * @param Metadata                    $metadata         Entity metadata
     * @param int|string|LocalizedCatalog $localizedCatalog LocalizedCatalog
     */
    public function createIndex(Metadata $metadata, LocalizedCatalog|int|string $localizedCatalog): Index
    {
        if (null === $metadata->getEntity()) {
            throw new LogicException('Invalid metadata: no entity');
        }

        $indexSettings = [
            'settings' => $this->indexSettings->getCreateIndexSettings() + $this->indexSettings->getDynamicIndexSettings($metadata, $localizedCatalog),
        ];
        $indexSettings['mappings'] = $this->metadataManager->getMapping($metadata)->asArray();
        $newIndexAliases = $this->indexSettings->getNewIndexMetadataAliases($metadata->getEntity(), $localizedCatalog);
        if (!empty($newIndexAliases)) {
            $indexSettings['aliases'] = array_fill_keys($newIndexAliases, ['is_hidden' => true]);
        }

        return $this->indexRepository->create(
            $this->indexSettings->createIndexNameFromIdentifier($metadata->getEntity(), $localizedCatalog),
            $indexSettings
        );
    }

    /**
     * Updated the mapping of an index according to current computed mapping
     * This is use as a real-time update when changing field configurations.
     *
     * @param Metadata                    $metadata         Entity metadata
     * @param int|string|LocalizedCatalog $localizedCatalog Localized catalog
     * @param array                       $fields           The fields to update. Default to all.
     */
    public function updateMapping(Metadata $metadata, LocalizedCatalog|int|string $localizedCatalog, $fields = []): void
    {
        try {
            // Mapping cannot be generated when the source list is empty, this is the case when the fixtures execution.
            if (0 === \count($metadata->getSourceFields())) {
                return;
            }

            $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier($metadata->getEntity(), $localizedCatalog);
            $mapping = $this->metadataManager->getMapping($metadata)->asArray();
            $installedMapping = $this->indexRepository->getMapping($indexAlias);
            $installedMapping = reset($installedMapping)['mappings']['properties'];
            if (!empty($fields)) {
                $properties = $mapping['properties'] ?? [];
                if (!empty($properties) && \is_array($properties)) {
                    $properties = array_filter(
                        $properties,
                        function ($key) use ($fields) {
                            return \in_array($key, $fields, true);
                        },
                        \ARRAY_FILTER_USE_KEY
                    );

                    // The include_in_root parameter can't be updated on live index.
                    // We need to be sure that the value we set in the same as the one in the current installed index.
                    foreach ($properties as $code => $property) {
                        if (FieldInterface::FIELD_TYPE_NESTED === $property['type']) {
                            $properties[$code]['include_in_root'] = $installedMapping[$code]['include_in_root'] ?? false;
                        }
                    }

                    $mapping['properties'] = $properties;
                }
            }
            $this->indexRepository->putMapping($indexAlias, $mapping);
        } catch (Missing404Exception $exception) {
            // Do nothing, we cannot update mapping of a non existing indexAlias.
        }
    }

    /**
     * Install index
     * - apply definitive settings
     * - add the correct index alias while removing it from the older index.
     *
     * @param string $indexName index name
     */
    public function installIndexByName(string $indexName): void
    {
        $this->indexRepository->refresh([$indexName]);
        $this->indexRepository->putSettings($indexName, $this->indexSettings->getInstallIndexSettings());
        $this->indexRepository->forceMerge($indexName);

        $indexAlias = $this->getInstalledIndexAlias($indexName);
        if (!empty($indexAlias)) {
            $this->proceedInstallIndex($indexName, $indexAlias);
        }
        // TODO else throw an error ?
    }

    /**
     * Proceed to the indices install :
     *  1) First switch the alias to the new index
     *  2) Remove old indices.
     *
     * @param string $indexName  Real index name
     * @param string $indexAlias Index alias (must include catalog identifier)
     */
    public function proceedInstallIndex(string $indexName, string $indexAlias): void
    {
        $aliasActions = [];
        $toDeleteIndices = [];

        $aliasActions[] = [
            'add' => ['index' => $indexName, 'alias' => $indexAlias],
        ];
        try {
            $oldIndices = array_keys($this->indexRepository->getMapping($indexAlias));
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            $oldIndices = [];
        }
        foreach ($oldIndices as $oldIndexName) {
            if ($oldIndexName != $indexName) {
                $toDeleteIndices[] = $oldIndexName;
                $aliasActions[] = ['remove' => ['index' => $oldIndexName, 'alias' => $indexAlias]];
            }
        }

        if (!empty($aliasActions)) {
            $this->indexRepository->updateAliases($aliasActions);
        }

        foreach ($toDeleteIndices as $toDeleteIndex) {
            $this->indexRepository->delete($toDeleteIndex);
        }
    }

    /**
     * Return the index alias to apply to the installed index.
     *
     * @param string $indexName Index name
     */
    protected function getInstalledIndexAlias(string $indexName): string|null
    {
        $installIndexAlias = null;

        $index = $this->indexRepository->findByName($indexName);
        $entityType = $index->getEntityType();
        $localizedCatalog = $index->getLocalizedCatalog();
        if (!empty($entityType) && !empty($localizedCatalog)) {
            $installIndexAlias = $this->indexSettings->getIndexAliasFromIdentifier($entityType, $localizedCatalog);
        }

        return $installIndexAlias;
    }
}
