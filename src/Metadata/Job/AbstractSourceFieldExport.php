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

namespace Gally\Metadata\Job;

use Gally\Doctrine\Service\EntityManagerFactory;
use Gally\Job\Entity\Job;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\Csv\AbstractCsvExport;
use Gally\Job\Service\JobManager;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Repository\SourceFieldRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractSourceFieldExport extends AbstractCsvExport
{
    public const METADATA_ENTITY = '';

    public const BASE_CSV_HEADERS = [
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
    ];

    protected SourceFieldRepository $sourceFieldRepository;
    protected Metadata $metadata;

    public function __construct(
        protected JobManager $jobManager,
        protected EntityManagerFactory $entityManagerFactory,
        protected Filesystem $filesystem,
        protected TranslatorInterface $translator,
        private int $batchSize = 100,
    ) {
        parent::__construct($translator, $jobManager, $entityManagerFactory, $filesystem, static::JOB_PROFILE);
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
        $this->metadata = $metadataRepository->findByEntity(static::METADATA_ENTITY);

        $this->initRepositories();

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

        fputcsv($handle, static::CSV_HEADERS, escape: '\\');

        $processedCount = 0;
        $sourceFields = $this->getSourceFieldsBatch($sourceFieldCount);

        foreach ($sourceFields as $batch) {
            foreach ($batch as $sourceField) {
                fputcsv($handle, $this->formatSourceFieldLine($sourceField), escape: '\\');
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

    /**
     * Yield batches of source fields (or related entities) to export.
     *
     * @return iterable<array>
     */
    abstract protected function getSourceFieldsBatch(int $totalCount): iterable;

    /**
     * Format a single line for CSV export.
     */
    abstract protected function formatSourceFieldLine(mixed $sourceFieldData): array;

    /**
     * Format a source field's core properties into an array.
     */
    protected function formatCoreSourceFieldData(SourceField $sourceField): array
    {
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
        ];
    }

    protected function formatNullableBoolean(?bool $value): string
    {
        if (null === $value) {
            return '';
        }

        return $this->formatBoolean($value);
    }

    /**
     * Initialize additional repositories after EntityManager recreation.
     */
    protected function initRepositories(): void
    {
    }
}
