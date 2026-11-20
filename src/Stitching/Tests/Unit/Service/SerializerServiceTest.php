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

namespace Gally\Stitching\Tests\Unit\Service;

use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Service\MetadataSourceFieldProviderCache;
use Gally\Stitching\Service\SerializerService;
use PHPUnit\Framework\TestCase;

class SerializerServiceTest extends TestCase
{
    public function testObjectSourceFieldsAreNotStitched(): void
    {
        $metadata = new Metadata();
        $sourceFields = [];
        foreach (['_stats' => SourceField\Type::TYPE_OBJECT, 'name' => SourceField\Type::TYPE_TEXT] as $code => $type) {
            $sourceField = (new SourceField())->setCode($code)->setType($type);
            $metadata->addSourceField($sourceField);
            $sourceFields[] = $sourceField;
        }

        $metadataRepository = $this->createMock(MetadataRepository::class);
        $metadataRepository->method('findByEntity')->willReturn($metadata);
        $sourceFieldProvider = $this->createMock(MetadataSourceFieldProviderCache::class);
        $sourceFieldProvider->method('getSourceFields')->willReturn($sourceFields);

        $config = (new SerializerService($metadataRepository, $sourceFieldProvider))->getStitchingConfigFromSourceFields('product');

        $this->assertSame(['name'], array_keys($config));
    }
}
