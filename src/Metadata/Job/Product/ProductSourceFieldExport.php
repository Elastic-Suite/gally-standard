<?php

/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Metadata\Job\Product;

use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Service\JobManager;
use Gally\Metadata\Job\AbstractSourceFieldExport;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSourceFieldExport extends AbstractSourceFieldExport
{
    public const JOB_PROFILE = 'sourcefield_export';
    public const METADATA_ENTITY = 'product';

    private ConfigurationRepository $facetConfigRepository;

    public const CSV_HEADERS = [
        ...parent::BASE_CSV_HEADERS,
        // Facet configuration
        'display_mode',
        'coverage_rate',
        'max_size',
        'sort_order',
        'position',
        'boolean_logic',
    ];

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected Filesystem $filesystem,
        protected TranslatorInterface $translator,
        private int $batchSize = 100,
    ) {
        parent::__construct($jobManager, $entityManagerFactory, $filesystem, $translator, $this->batchSize);
    }

    protected function initRepositories(): void
    {
        $this->facetConfigRepository = $this->exportEntityManager->getRepository(Configuration::class);
        $this->facetConfigRepository->setMetadata($this->metadata);
    }

    protected function getSourceFieldsBatch(int $totalCount): iterable
    {
        for ($offset = 0; $offset < $totalCount; $offset += $this->batchSize) {
            yield $this->facetConfigRepository->findByWithSourceFields([], ['id' => 'ASC'], $this->batchSize, $offset);
        }
    }

    protected function formatSourceFieldLine(mixed $sourceFieldData): array
    {
        /** @var Configuration $sourceFieldWithConfig */
        $sourceFieldWithConfig = $sourceFieldData;
        $sourceField = $sourceFieldWithConfig->getSourceField();

        return array_merge(
            $this->formatCoreSourceFieldData($sourceField),
            [
                $sourceFieldWithConfig->getDisplayMode(),
                $sourceFieldWithConfig->getCoverageRate(),
                $sourceFieldWithConfig->getMaxSize(),
                $sourceFieldWithConfig->getSortOrder(),
                $sourceFieldWithConfig->getPosition(),
                $sourceFieldWithConfig->getBooleanLogic(),
            ]
        );
    }
}
