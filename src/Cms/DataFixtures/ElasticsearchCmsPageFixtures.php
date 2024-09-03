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

namespace Gally\Cms\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Gally\Fixture\Service\ElasticsearchFixturesInterface;
use Gally\Fixture\Service\EntityIndicesFixturesInterface;

class ElasticsearchCmsPageFixtures extends Fixture
{
    public function __construct(
        private ElasticsearchFixturesInterface $elasticsearchFixtures,
        private EntityIndicesFixturesInterface $entityIndicesFixtures,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->entityIndicesFixtures->createEntityElasticsearchIndices('cms_page');
        $this->elasticsearchFixtures->loadFixturesDocumentFiles([__DIR__ . '/sample_data/elasticsearch/cms_page_documents.json']);
    }
}
