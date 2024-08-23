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

namespace Gally\Metadata\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Service\IndexOperation;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Model\Metadata;
use Gally\Metadata\Model\SourceField;
use Psr\Log\LoggerInterface;

class SpreadSourceFieldData
{
    private array $updateMappingFields = [
        'is_searchable',
        'is_filterable',
        'search_weight',
        'is_used_for_sort_by',
        'is_used_in_spellcheck',
        'is_used_in_autocomplete',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MetadataManager $metadataManager,
        private IndexOperation $indexOperation,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->spreadSourceFieldData($args->getObject(), true);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->spreadSourceFieldData($args->getObject(), false);
    }

    private function spreadSourceFieldData(object $entity, bool $isNewSourceField): void
    {
        if (!$entity instanceof SourceField) {
            return;
        }
        $sourceField = $entity;
        [$fieldsToUpdate, $updateMapping] = $this->checkUpdateNeeded($sourceField, $isNewSourceField);

        // Keep only parent field for nested fields.
        $fieldsToUpdate = array_map(fn ($fieldsToUpdate) => explode('.', $fieldsToUpdate)[0] ?? $fieldsToUpdate, $fieldsToUpdate);
        $fieldsToUpdate = array_unique($fieldsToUpdate);

        if ($updateMapping) {
            $this->updateMapping($fieldsToUpdate, $sourceField->getMetadata());
        }
    }

    private function checkUpdateNeeded(SourceField $sourceField, bool $isNewSourceField): array
    {
        $updateMapping = false;
        $options = $this->getMappingFieldOptions($sourceField);
        if ($isNewSourceField) {
            return [array_keys($options), true];
        }

        $origOptions = $this->getOriginalMappingFieldOptions($sourceField);

        $fields = array_unique(array_merge(array_keys($origOptions), array_keys($options)));
        foreach ($fields as $field) {
            foreach ($this->updateMappingFields as $mappingField) {
                $origValue = (int) ($origOptions[$field][$mappingField] ?? false);
                $value = (int) ($options[$field][$mappingField] ?? false);

                if ($origValue !== $value) {
                    if ('search_weight' === $mappingField) {
                        if ((1 === $origValue) && ($value > $origValue)) {
                            // Search weight moved from 1 to more. Mapping will change, so data need to be reindexed.
                            $updateMapping = true;
                            break 2;
                        }
                        continue;
                    }

                    // If option is disabled, we do nothing. Data will remain until next full reindex.
                    if (true === (bool) ($options[$field][$mappingField] ?? false)) {
                        // Configuration for is_searchable, is_filterable, etc... has been enabled. Mapping needs to be updated.
                        $updateMapping = true;
                        break 2;
                    }
                }
            }
        }

        return [$fields, $updateMapping];
    }

    private function updateMapping(array $fieldsToUpdate, Metadata $metadata): void
    {
        try {
            foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
                $this->indexOperation->updateMapping($metadata, $localizedCatalog, $fieldsToUpdate);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }
    }

    /**
     * Get the original (prior edit) mapping field configuration from an attribute.
     */
    private function getOriginalMappingFieldOptions(SourceField $sourceField): array
    {
        $origSourceField = clone $sourceField;
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($sourceField);

        // Remove labels from change set because it create conflict with
        // \Gally\Metadata\State\SourceFieldProcessor::replaceLabels
        // and labels doesn't affect mapping definition.
        unset($changeSet['labels']);

        foreach ($changeSet as $field => $values) {
            $setFunction = 'set' . ucfirst($field);
            if (method_exists($origSourceField, $setFunction)) {
                $origValue = $values[0];
                $origSourceField->{$setFunction}($origValue);
            }
        }

        return $this->getMappingFieldOptions($origSourceField);
    }

    /**
     * Get a mapping field configuration from an attribute.
     */
    private function getMappingFieldOptions(SourceField $sourceField): array
    {
        $mappingFieldOptions = [];
        foreach ($this->metadataManager->getFields($sourceField) as $field) {
            $mappingFieldOptions[$field->getName()] = $field->getConfig();
        }

        return $mappingFieldOptions;
    }
}
