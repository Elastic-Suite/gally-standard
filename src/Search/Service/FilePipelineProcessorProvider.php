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

use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField\Type;
use Gally\Metadata\Repository\SourceFieldRepository;

class FilePipelineProcessorProvider implements IngestPipelineProcessorProvider
{
    public function __construct(
        protected SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    public function getProcessors(Metadata $metadata): array
    {
        $fileSourceFields = $this->sourceFieldRepository->findBy([
            'type' => Type::TYPE_FILE,
            'metadata' => $metadata,
        ]);

        $processors = [];

        foreach ($fileSourceFields as $sourceField) {
            $processors[] = [
                'attachment' => [
                    'field' => $sourceField->getCode(),
                    'target_field' => $sourceField->getCode() . '_content',
                    'ignore_missing' => true,
                ],
                'remove' => [
                    'field' => $sourceField->getCode(),
                    'ignore_missing' => true,
                ],
            ];
        }

        return $processors;
    }
}
