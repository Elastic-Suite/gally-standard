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

namespace Gally\Search\Service;

use Gally\Metadata\Model\Metadata;

interface IngestPipelineProcessorProvider
{
    /**
     * Create Pipeline processors by metadata.
     *
     * @param Metadata $metadata metadata
     */
    public function getProcessors(Metadata $metadata): array;
}
