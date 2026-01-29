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

namespace Gally\Index\Repository\DataStream;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Exception\LogicException;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Dto\Bulk;
use Gally\Index\Entity\DataStream;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Repository\IndexStateManagement\IndexStateManagementRepositoryInterface;
use Gally\Index\Repository\IndexTemplate\IndexTemplateRepositoryInterface;
use Gally\Metadata\Entity\Metadata;
use OpenSearch\Client;
use Psr\Log\LoggerInterface;

class DataStreamRepository implements DataStreamRepositoryInterface
{
    public function __construct(
        private Client $client,
        private IndexSettingsInterface $indexSettings,
        private IndexTemplateRepositoryInterface $indexTemplateRepository,
        private IndexStateManagementRepositoryInterface $ismRepository,
        private IndexRepositoryInterface $indexRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function createForEntity(Metadata $metadata, LocalizedCatalog $localizedCatalog): DataStream
    {
        if (null === $metadata->getEntity()) {
            throw new LogicException('Invalid metadata: no entity');
        }

        if (!$metadata->isTimeSeriesData()) {
            throw new LogicException('Cannot create data stream for non-time-series entity');
        }

        $template = $this->indexTemplateRepository->findByMetadata($metadata, $localizedCatalog);
        if (null === $template) {
            $template = $this->indexTemplateRepository->createForEntity(
                $metadata,
                $localizedCatalog,
            );
        }

        $ism = $this->ismRepository->findByMetadata($metadata, $localizedCatalog);
        if (null === $ism) {
            $this->ismRepository->createForEntity(
                $metadata,
                $localizedCatalog,
            );
        }

        return $this->create($metadata->getEntity(), $localizedCatalog, $template);
    }

    public function create(string $identifier, LocalizedCatalog $localizedCatalog, ?\Gally\Index\Entity\IndexTemplate $template = null): DataStream
    {
        $dataStreamName = $this->indexSettings->getIndexAliasFromIdentifier($identifier, $localizedCatalog);
        $this->client->indices()->createDataStream(['name' => $dataStreamName]);

        return $this->findByName($identifier, $localizedCatalog);
    }

    public function findByMetadata(Metadata $metadata, LocalizedCatalog $localizedCatalog): ?DataStream
    {
        return $this->findByName($metadata->getEntity(), $localizedCatalog);
    }

    public function findByName(string $name, LocalizedCatalog $localizedCatalog): ?DataStream
    {
        $identifier = $this->indexSettings->getIndexAliasFromIdentifier($name, $localizedCatalog);

        return $this->findById($identifier);
    }

    public function findById(string $identifier): ?DataStream
    {
        try {
            $response = $this->client->indices()->getDataStream(['name' => $identifier]);

            if (!empty($response['data_streams'])) {
                return $this->createFromResponse($response['data_streams'][0]);
            }
        } catch (\Exception) {
            // Data stream not found, no need to log.
        }

        return null;
    }

    public function findAll(): array
    {
        $dataStreams = [];

        try {
            $response = $this->client->indices()->getDataStream();

            foreach ($response['data_streams'] as $dataStreamData) {
                $dataStreams[] = $this->createFromResponse($dataStreamData);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }

        return $dataStreams;
    }

    public function bulk(Bulk\Request $request, bool $instantRefresh = false): Bulk\Response
    {
        $data = ['body' => $request->getOperations()];

        return new Bulk\Response($this->client->bulk($data));
    }

    public function delete(string $id): void
    {
        $dataStream = $this->findById($id);
        $this->client->indices()->deleteDataStream(['name' => $dataStream->getName()]);

        $this->ismRepository->delete($id);

        if ($dataStream->getTemplate()) {
            $this->indexTemplateRepository->delete($dataStream->getTemplate()->getId());
        }
    }

    private function createFromResponse(array $data): DataStream
    {
        $template = null;
        $localizedCatalog = null;
        $entityType = null;

        if (isset($data['template'])) {
            $template = $this->indexTemplateRepository->findById($data['template']);
            if ($template) {
                $localizedCatalog = $template->getLocalizedCatalog();
                $entityType = $template->getName();
            }
        }

        $dataStream = new DataStream(
            $data['name'],
            $data['status'] ?? 'active',
            $template,
            $entityType,
            $localizedCatalog,
        );

        if (isset($data['indices']) && \is_array($data['indices'])) {
            foreach ($data['indices'] as $indexData) {
                $indexName = \is_array($indexData) ? ($indexData['index_name'] ?? '') : $indexData;
                if ($indexName) {
                    $index = $this->indexRepository->findByName($indexName);
                    if ($index) {
                        $dataStream->addIndex($index);
                    }
                }
            }
        }

        return $dataStream;
    }
}
