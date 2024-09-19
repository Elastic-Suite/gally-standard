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

namespace Gally\Search\Repository\Ingest;

use Gally\Metadata\Entity\Metadata;
use Gally\Search\Entity\IngestPipeline;
use Gally\Search\Service\IngestPipelineProcessorProvider;
use OpenSearch\Client;
use OpenSearch\Common\Exceptions\Missing404Exception;

class PipelineRepository implements PipelineRepositoryInterface
{
    /**
     * @param IngestPipelineProcessorProvider[] $processorsProviders
     */
    public function __construct(
        protected Client $client,
        protected string $pipelinePrefix,
        private iterable $processorsProviders,
    ) {
    }

    public function create(string $name, string $description, array $processors): ?IngestPipeline
    {
        $query = [
            'id' => $name,
            'body' => [
                'description' => $description,
                'processors' => $processors,
            ],
        ];
        $this->client->ingest()->putPipeline($query);

        return new IngestPipeline($name, $description, $processors);
    }

    public function get(string $name): ?IngestPipeline
    {
        try {
            $data = $this->client->ingest()->getPipeline(['id' => $name]);
            $description = '';
            if (\array_key_exists('description', $data[$name])) {
                $description = $data[$name]['description'];
            }

            return new IngestPipeline($name, $description, $data[$name]['processors']);
        } catch (Missing404Exception $exception) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    public function createByMetadata(Metadata $metadata): ?IngestPipeline
    {
        $pipelineName = $this->pipelinePrefix . $metadata->getEntity();
        $processors = [];

        foreach ($this->processorsProviders as $processorsProvider) {
            $processors = array_merge($processors, $processorsProvider->getProcessors($metadata));
        }

        return empty($processors)
            ? null
            : $this->create($pipelineName, $pipelineName, $processors);
    }
}
