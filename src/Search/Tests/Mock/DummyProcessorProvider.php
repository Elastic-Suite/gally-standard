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

namespace Gally\Search\Tests\Mock;

use Gally\Metadata\Entity\Metadata;
use Gally\Search\Service\IngestPipelineProcessorProvider;

class DummyProcessorProvider implements IngestPipelineProcessorProvider
{
    public function getProcessors(Metadata $metadata): array
    {
        return 'category' === $metadata->getEntity()
            ? [
                [
                    'set' => [
                        'field' => 'dummy',
                        'value' => ['test'],
                    ],
                ],
            ]
            : [];
    }
}
