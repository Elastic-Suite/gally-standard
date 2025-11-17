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

namespace Gally\Metadata\Fixture;

use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;

class EntityProvider
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    public function findSourceFieldByCode(string $entity, string $code): SourceField
    {
        $metadata = $this->metadataRepository->findByEntity($entity);

        return $this->sourceFieldRepository->findOneBy(['metadata' => $metadata, 'code' => $code]);
    }

    public function findSourceFieldIdByCode(string $entity, string $code): int
    {
        return $this->findSourceFieldByCode($entity, $code)->getId();
    }
}
