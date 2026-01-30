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

namespace Gally\Index\Repository\IndexTemplate;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\IndexTemplate;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\Metadata;
use OpenSearch\Client;
use Psr\Log\LoggerInterface;

class IndexTemplateRepository implements IndexTemplateRepositoryInterface
{
    public function __construct(
        private Client $client,
        private IndexSettingsInterface $indexSettings,
        private MetadataManager $metadataManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Creates an index template for a given entity metadata and catalog.
     *
     * @param Metadata         $metadata         Entity metadata
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     */
    public function createForEntity(
        Metadata $metadata,
        LocalizedCatalog $localizedCatalog,
    ): IndexTemplate {
        return $this->create(
            $metadata->getEntity(),
            $localizedCatalog,
            [$this->indexSettings->getIndexAliasFromIdentifier($metadata->getEntity(), $localizedCatalog)],
            $this->indexSettings->getDynamicIndexSettings($metadata, $localizedCatalog),
            $this->metadataManager->getMapping($metadata)->asArray(),
            $metadata->isTimeSeriesData(),
        );
    }

    /**
     * Creates an index template.
     *
     * @param string           $indexIdentifier  Index identifier
     * @param LocalizedCatalog $localizedCatalog LocalizedCatalog
     * @param array            $indexSettings    Index settings
     * @param array            $mappings         Index mappings
     * @param bool             $isDataStream     Is index template for data stream
     * @param int|null         $priority         Ism pattern priority
     */
    public function create(
        string $indexIdentifier,
        LocalizedCatalog $localizedCatalog,
        array $indexPatterns,
        array $indexSettings,
        array $mappings,
        bool $isDataStream = false,
        ?int $priority = null,
    ): IndexTemplate {
        $template = new IndexTemplate(
            $indexIdentifier,
            $indexPatterns,
            $priority,
            $isDataStream
        );

        $template->setAliases($this->indexSettings->getNewIndexMetadataAliases($indexIdentifier, $localizedCatalog));
        $template->setSettings($this->indexSettings->getCreateIndexSettings() + $indexSettings);
        $template->setMappings($mappings);
        $template->setLocalizedCatalog($localizedCatalog);

        return $this->save($template);
    }

    public function findByMetadata(Metadata $metadata, LocalizedCatalog $localizedCatalog): ?IndexTemplate
    {
        return $this->findByName($metadata->getEntity(), $localizedCatalog);
    }

    public function findByName(string $name, LocalizedCatalog $localizedCatalog): ?IndexTemplate
    {
        $templateId = $this->indexSettings->createIndexTemplateNameFromIdentifier($name, $localizedCatalog);

        return $this->findById($templateId);
    }

    public function findById(string $id): ?IndexTemplate
    {
        try {
            $response = $this->client->indices()->getIndexTemplate(['name' => $id]);

            if (!empty($response['index_templates'])) {
                return $this->createFromResponse($response['index_templates'][0]);
            }
        } catch (\Exception $exception) {
            // Index template not found, no need to log.
        }

        return null;
    }

    /**
     * @return IndexTemplate[]
     */
    public function findAll(): array
    {
        $templates = [];
        try {
            $response = $this->client->indices()->getIndexTemplate();

            foreach ($response['index_templates'] as $templateData) {
                $templates[] = $this->createFromResponse($templateData);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }

        return $templates;
    }

    public function update(IndexTemplate $template): IndexTemplate
    {
        return $this->save($template);
    }

    public function delete(string $id): void
    {
        $this->client->indices()->deleteIndexTemplate(['name' => $id]);
    }

    private function save(IndexTemplate $template): IndexTemplate
    {
        $body = [
            'index_patterns' => $template->getIndexPatterns(),
            'template' => array_filter([
                'aliases' => array_fill_keys($template->getAliases(), ['is_hidden' => true]),
                'settings' => $template->getSettings(),
                'mappings' => $template->getMappings(),
            ]),
        ];

        if ($template->isDataStreamTemplate()) {
            $body['data_stream'] = new \stdClass();
        }

        $templateId = $this->indexSettings->createIndexTemplateNameFromIdentifier(
            $template->getName(),
            $template->getLocalizedCatalog()
        );
        $this->client->indices()->putIndexTemplate(['name' => $templateId, 'body' => array_filter($body)]);

        return $this->findByName($template->getName(), $template->getLocalizedCatalog());
    }

    private function createFromResponse(array $data): IndexTemplate
    {
        $template = new IndexTemplate(
            $data['name'],
            $data['index_template']['index_patterns'],
            $data['index_template']['priority'] ?? null,
            isset($data['index_template']['data_stream']),
        );

        $template->setId($data['name']);
        $template->setAliases(array_keys($data['index_template']['template']['aliases'] ?? []));
        $template->setSettings($data['index_template']['template']['settings'] ?? []);
        $template->setMappings($data['index_template']['template']['mappings'] ?? []);

        $entity = $this->indexSettings->extractEntityFromAliases($template);
        $template->setName($entity ?? $data['name']);
        try {
            $template->setLocalizedCatalog($this->indexSettings->extractCatalogFromAliases($template));
        } catch (\InvalidArgumentException $exception) {
            // Ignore missing localized catalog because of unit test.
        }

        return $template;
    }
}
