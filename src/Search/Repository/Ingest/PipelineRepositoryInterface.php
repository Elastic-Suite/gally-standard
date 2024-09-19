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

interface PipelineRepositoryInterface
{
    /**
     * Create ingest pipeline.
     *
     * @param string $name        name pipeline
     * @param string $description description pipeline
     * @param array  $processors  processors pipeline
     */
    public function create(string $name, string $description, array $processors): ?IngestPipeline;

    /**
     * Get Pipeline by name.
     */
    public function get(string $name): ?IngestPipeline;

    /**
     * Create Pipeline by metadata.
     *
     * @param Metadata $metadata metadata
     */
    public function createByMetadata(Metadata $metadata): ?IngestPipeline;
}
