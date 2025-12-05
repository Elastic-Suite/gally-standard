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
use Gally\Exception\LogicException;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\IndexTemplate;
use Gally\Index\Service\MetadataManager;
use Gally\Metadata\Entity\Metadata;
use OpenSearch\Client;

class IndexTemplateRepository implements IndexTemplateRepositoryInterface
{
    public function __construct(
        private Client $client,
        private IndexSettingsInterface $indexSettings,
        protected MetadataManager $metadataManager,
    ) {
    }

    /**
     * Creates an index for a given entity metadata and catalog.
     *
     * @param Metadata                    $metadata         Entity metadata
     * @param int|string|LocalizedCatalog $localizedCatalog LocalizedCatalog
     */
    public function createEntityIndexTemplate(
        Metadata $metadata,
        LocalizedCatalog|int|string $localizedCatalog,
        array $indexPatterns,
    ): IndexTemplate {
        if (null === $metadata->getEntity()) {
            throw new LogicException('Invalid metadata: no entity');
        }

        return $this->createIndexTemplate(
            $metadata->getEntity(),
            $localizedCatalog,
            $indexPatterns,
            $this->indexSettings->getCreateIndexSettings() + $this->indexSettings->getDynamicIndexSettings($metadata, $localizedCatalog),
            $this->metadataManager->getMapping($metadata)->asArray(),
            $metadata->isTimeSeriesData(),
        );
    }

    /**
     * Creates an index for a given entity metadata and catalog.
     *
     * @param string                      $indexIdentifier  Index identifier
     * @param int|string|LocalizedCatalog $localizedCatalog LocalizedCatalog
     * @param array                       $indexSettings    Index settings
     */
    public function createIndexTemplate(
        string $indexIdentifier,
        LocalizedCatalog|int|string $localizedCatalog,
        array $indexPatterns,
        array $indexSettings,
        array $mappings,
        bool $isDataStream = false,
        ?int $priority = null,
    ): IndexTemplate {
        $template = new IndexTemplate(
            $this->indexSettings->createIndexTemplaceNameFromIdentifier($indexIdentifier, $localizedCatalog),
            $indexPatterns,
            $priority,
            $isDataStream
        );

        $template->setAliases($this->indexSettings->getNewIndexMetadataAliases($indexIdentifier, $localizedCatalog));
        $template->setSettings($indexSettings);
        $template->setMappings($mappings);

        return $this->save($template);
    }

    /**
     * @return IndexTemplate[]
     */
    public function findAll(): array
    {
        $templates = [];

        try {
            $response = $this->client->indices()->getIndexTemplate();
            $prefix = $this->indexSettings->getIndexTemplatePrefix();

            foreach ($response['index_templates'] as $templateData) {
                if (str_starts_with($templateData['name'], $prefix)) {
                    $templates[] = $this->createFromResponse($templateData);
                }
            }
        } catch (\Exception $e) {
            // Log exception if needed
        }

        return $templates;
    }

    public function findByName(string $name): ?IndexTemplate
    {
        try {
            $response = $this->client->indices()->getIndexTemplate(['name' => $name]);

            if (!empty($response['index_templates'])) {
                return $this->createFromResponse($response['index_templates'][0]);
            }
        } catch (\Exception $e) {
            // Log exception if needed
        }

        return null;
    }

    public function save(IndexTemplate $template): IndexTemplate
    {
        $body = [
            'index_patterns' => $template->getIndexPatterns(),
            'template' => [],
        ];

        if (null !== $template->getPriority()) {
            $body['priority'] = $template->getPriority();
        }

        if ([] !== $template->getAliases()) {
            $body['template']['aliases'] = array_fill_keys($template->getAliases(), ['is_hidden' => true]);
        }

        if ([] !== $template->getSettings()) {
            $body['template']['settings'] = $template->getSettings();
        }

        if ([] !== $template->getMappings()) {
            $body['template']['mappings'] = $template->getMappings();
        }

        if ($template->isDataStreamTemplate()) {
            $body['data_stream'] = new \stdClass();
        }

        $this->client->indices()->putIndexTemplate(['name' => $template->getName(), 'body' => array_filter($body)]);

        return $this->findByName($template->getName());
    }

    public function delete(string $name): void
    {
        $this->client->indices()->deleteIndexTemplate(['name' => $name]);
    }

    private function createFromResponse(array $data): IndexTemplate
    {
        $template = new IndexTemplate(
            $data['name'],
            $data['index_template']['index_patterns'],
            $data['index_template']['priority'] ?? null,
            isset($data['index_template']['data_stream']),
        );

        $template->setAliases(array_keys($data['index_template']['template']['aliases'] ?? []));
        $template->setSettings($data['index_template']['template']['settings'] ?? []);
        $template->setMappings($data['index_template']['template']['mappings'] ?? []);

        $template->setEntityType($this->indexSettings->extractEntityFromAliases($template));
        $template->setLocalizedCatalog($this->indexSettings->extractCatalogFromAliases($template));

        return $template;
    }
}
