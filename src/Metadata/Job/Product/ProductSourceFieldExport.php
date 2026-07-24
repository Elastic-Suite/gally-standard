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
use Gally\Job\Entity\Job;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\Csv\AbstractCsvExport;
use Gally\Job\Service\JobManager;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSourceFieldExport extends AbstractCsvExport
{
    public const JOB_PROFILE = 'sourcefield_export';
    public const METADATA_ENTITY = 'product';

    private SourceFieldRepository $sourceFieldRepository;
    private ConfigurationRepository $facetConfigRepository;
    private Metadata $metadata;

    public const CSV_HEADERS = [
        // Source field
        'code',
        'label',
        'type',
        'weight',
        'is_searchable',
        'is_filterable',
        'is_sortable',
        'is_spellchecked',
        'is_used_for_rules',
        'is_used_in_autocomplete',
        'is_spannable', 
        'analyzer',
        // Facet configuration
        'display_mode', 
        'coverage_rate',
        'max_size',
        'sort_order',
        'position',
        'boolean_logic'
    ];

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected Filesystem $filesystem,
        protected TranslatorInterface $translator,
        private int $batchSize = 100,
    ) {
        parent::__construct($translator, $jobManager, $entityManagerFactory, $filesystem, self::JOB_PROFILE);
    }

    public function getLabel(): string
    {
        return $this->translator->trans('sourcefield.export.label', [], 'gally_sourcefield');
    }

    public function process(): void
    {
        $this->exportEntityManager = $this->entityManagerFactory->createIsolatedEntityManager();
        $this->sourceFieldRepository = $this->exportEntityManager->getRepository(SourceField::class);
        /** @var MetadataRepository $metadataRepository */
        $metadataRepository = $this->exportEntityManager->getRepository(Metadata::class);
        $this->metadata = $metadataRepository->findByEntity(self::METADATA_ENTITY);
        $this->facetConfigRepository = $this->exportEntityManager->getRepository(Configuration::class);
        $this->facetConfigRepository->setMetadata($this->metadata);

        $this->isCurrentJobSet();
        $this->logInfo('sourcefield.export.started', 'gally_sourcefield', ['%job_id%' => $this->currentJob->getId()]);

        $sourceFieldCount = $this->sourceFieldRepository->count(['metadata' => $this->metadata]);

        if (0 === $sourceFieldCount) {
            $this->logInfo('sourcefield.export.no_data', 'gally_sourcefield');

            return;
        }

        [$filepath, $fileName] = $this->prepareExportFile('sourcefield');

        $this->generateCsvExport($sourceFieldCount, $this->currentJob, $filepath);

        $this->jobManager->updateJobFile($this->currentJob, $fileName);

        $this->logInfo('sourcefield.export.completed', 'gally_sourcefield', [
            '%count%' => $sourceFieldCount,
            '%fileName%' => $fileName,
        ]);
    }

    protected function generateCsvExport(int $sourceFieldCount, Job $job, string $filepath): void
    {
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new JobException('Unable to create export file: ' . $filepath);
        }

        fputcsv($handle, self::CSV_HEADERS, escape: '\\');

        $processedCount = 0;
        for ($offset = 0; $offset < $sourceFieldCount; $offset += $this->batchSize) {
            // Instead of exporting source field directly, we export general facet configurations for all source fields
            // Source fields that do not have any configuration will be given default general configuration
            $sourceFieldsWithConfiguration = $this->facetConfigRepository->findByWithSourceFields([], ['id' => 'ASC'], $this->batchSize, $offset);

            foreach ($sourceFieldsWithConfiguration as $sourceFieldWithConfig) {
                fputcsv($handle, $this->formatSourceFieldLine($sourceFieldWithConfig), escape: '\\');
                ++$processedCount;
            }

            $this->logInfo('sourcefield.export.progress', 'gally_sourcefield', [
                '%processed%' => $processedCount,
                '%total%' => $sourceFieldCount,
            ]);
            $this->exportEntityManager->clear();
        }

        fclose($handle);
    }

    protected function formatSourceFieldLine(Configuration $sourceFieldWithConfig): array 
    {
        $sourceField = $sourceFieldWithConfig->getSourceField();
        return [
            $sourceField->getCode(),
            $sourceField->getDefaultLabel(),
            $sourceField->getType(),
            $sourceField->getWeight(),
            $this->formatNullableBoolean($sourceField->getIsSearchable()),
            $this->formatNullableBoolean($sourceField->getIsFilterable()),
            $this->formatNullableBoolean($sourceField->getIsSortable()),
            $this->formatNullableBoolean($sourceField->getIsSpellchecked()),
            $this->formatNullableBoolean($sourceField->getIsUsedForRules()),
            $this->formatNullableBoolean($sourceField->getIsUsedInAutocomplete()),
            $this->formatNullableBoolean($sourceField->getIsSpannable()),
            $sourceField->getDefaultSearchAnalyzer(),
            $sourceFieldWithConfig->getDisplayMode(),
            $sourceFieldWithConfig->getCoverageRate(),
            $sourceFieldWithConfig->getMaxSize(),
            $sourceFieldWithConfig->getSortOrder(),
            $sourceFieldWithConfig->getPosition(),
            $sourceFieldWithConfig->getBooleanLogic(),
        ];
    }

    protected function formatNullableBoolean(?bool $value): string
    {
        if (null === $value) {
            return '';
        }

        return $this->formatBoolean($value);
    }
}
