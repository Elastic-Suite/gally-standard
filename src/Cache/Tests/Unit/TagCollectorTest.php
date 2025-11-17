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

namespace Gally\Cache\Tests\Unit;

use ApiPlatform\State\ResourceList;
use Gally\Cache\Service\TagCollector;
use Gally\Test\AbstractTestCase;

class TagCollectorTest extends AbstractTestCase
{
    protected TagCollector $tagCollector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagCollector = static::getContainer()->get('api_platform.http_cache.tag_collector');
    }

    /**
     * @dataProvider collectCacheTagDataProvider
     */
    public function testCollectCacheTag(array $context, array $expectedResultContext): void
    {
        $this->tagCollector->collect($context);
        $this->assertSame($expectedResultContext, $context['resources']->getArrayCopy());
    }

    public function collectCacheTagDataProvider(): iterable
    {
        $resources = new ResourceList();

        yield [
            ['resources' => &$resources],
            [],
        ];

        yield [
            [
                'iri' => '/api/fakeEntity/1',
                'resources' => &$resources,
            ],
            [
                '/api/fakeEntity/1' => '/api/fakeEntity/1',
            ],
        ];

        yield [
            [
                'iri' => '/api/fakeEntity/2',
                'resources' => &$resources,
                'groups' => ['fakeNormalisationGroup'],
            ],
            [
                '/api/fakeEntity/1' => '/api/fakeEntity/1',
                '/api/fakeEntity/2' => '/api/fakeEntity/2',
            ],
        ];

        yield [
            [
                'iri' => '/api/fakeEntity/3',
                'resources' => &$resources,
                'groups' => ['source_field:read'],
            ],
            [
                '/api/fakeEntity/1' => '/api/fakeEntity/1',
                '/api/fakeEntity/2' => '/api/fakeEntity/2',
                '/api/fakeEntity/3' => '/api/fakeEntity/3',
            ],
        ];

        yield [
            [
                'iri' => '/api/fakeEntity/4',
                'resources' => &$resources,
                'groups' => ['source_field:read'],
                'resource_class' => 'Gally\Metadata\Entity\FakeClass',
            ],
            [
                '/api/fakeEntity/1' => '/api/fakeEntity/1',
                '/api/fakeEntity/2' => '/api/fakeEntity/2',
                '/api/fakeEntity/3' => '/api/fakeEntity/3',
                '/api/fakeEntity/4' => '/api/fakeEntity/4',
            ],
        ];

        yield [
            [
                'iri' => '/api/fakeEntity/5',
                'resources' => &$resources,
                'groups' => ['source_field:read'],
                'resource_class' => 'Gally\Metadata\Entity\SourceFieldLabel',
            ],
            [
                '/api/fakeEntity/1' => '/api/fakeEntity/1',
                '/api/fakeEntity/2' => '/api/fakeEntity/2',
                '/api/fakeEntity/3' => '/api/fakeEntity/3',
                '/api/fakeEntity/4' => '/api/fakeEntity/4',
            ],
        ];
    }
}
