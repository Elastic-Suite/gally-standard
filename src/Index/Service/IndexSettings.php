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

namespace Gally\Index\Service;

use Gally\Analysis\Service\Config;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Service\ConfigurationManager;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\Index;
use Gally\Index\Entity\IndexTemplate;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Repository\SourceFieldRepository;
use Gally\Search\Repository\Ingest\PipelineRepositoryInterface;

class IndexSettings implements IndexSettingsInterface
{
    /** @var string */
    public const FULL_REINDEX_REFRESH_INTERVAL = '30s';

    /** @var string */
    public const DIFF_REINDEX_REFRESH_INTERVAL = '1s';

    /** @var string */
    public const FULL_REINDEX_TRANSLOG_DURABILITY = 'async';

    /** @var string */
    public const DIFF_REINDEX_TRANSLOG_DURABILITY = 'request';

    /** @var string */
    public const CODEC = 'best_compression';

    /** @var int */
    public const TOTAL_FIELD_LIMIT = 20000;

    /** @var int */
    public const PER_SHARD_MAX_RESULT_WINDOW = 100000;

    /** @var int */
    public const MIN_SHINGLE_SIZE_DEFAULT = 2;

    /** @var int */
    public const MAX_SHINGLE_SIZE_DEFAULT = 2;

    /** @var int */
    public const MIN_NGRAM_SIZE_DEFAULT = 1;

    /** @var int */
    public const MAX_NGRAM_SIZE_DEFAULT = 2;

    /**
     * IndexSettings constructor.
     *
     * @param LocalizedCatalogRepository  $localizedCatalogRepository Catalog repository
     * @param Config                      $analysisConfig             Analysis configuration
     * @param SourceFieldRepository       $sourceFieldRepository      Source field repository
     * @param PipelineRepositoryInterface $pipelineRepository         Pipeline repository
     */
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private Config $analysisConfig,
        private ConfigurationManager $configurationManager,
        private SourceFieldRepository $sourceFieldRepository,
        private PipelineRepositoryInterface $pipelineRepository,
    ) {
    }

    /**
     * Create a new index name for a given entity/index identifier (eg. product) and catalog including current date.
     *
     * @param string                      $indexIdentifier  Index identifier
     * @param int|string|LocalizedCatalog $localizedCatalog The catalog
     */
    public function createIndexNameFromIdentifier(string $indexIdentifier, LocalizedCatalog|int|string $localizedCatalog): string
    {
        $indexNameSuffix = $this->getIndexNameSuffix(new \DateTime());

        return \sprintf('%s_%s', $this->getIndexAliasFromIdentifier($indexIdentifier, $localizedCatalog), $indexNameSuffix);
    }

    /**
     * Create a new ism name for an identifier (eg. product) by localized catalog.
     *
     * @param string           $identifier       Ism identifier
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     */
    public function createIsmNameFromIdentifier(string $identifier, LocalizedCatalog $localizedCatalog): string
    {
        return \sprintf('%s_%s_%s', $this->getIsmPrefix($localizedCatalog), $localizedCatalog->getCode(), $identifier);
    }

    /**
     * Create a new index template name for an identifier (eg. product) by localized catalog.
     *
     * @param string           $identifier       Index template identifier
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     */
    public function createIndexTemplateNameFromIdentifier(string $identifier, LocalizedCatalog $localizedCatalog): string
    {
        return \sprintf('%s_%s_%s', $this->getIndexTemplatePrefix($localizedCatalog), $localizedCatalog->getCode(), $identifier);
    }

    /**
     * Returns the index alias for an identifier (eg. product) by catalog.
     *
     * @param string                      $indexIdentifier  An index identifier
     * @param int|string|LocalizedCatalog $localizedCatalog The localized catalog
     */
    public function getIndexAliasFromIdentifier(string $indexIdentifier, int|string|LocalizedCatalog $localizedCatalog): string
    {
        $catalogCode = strtolower((string) $this->getCatalogCode($localizedCatalog));

        return \sprintf('%s_%s_%s', $this->getIndexNamePrefix(), $catalogCode, $indexIdentifier);
    }

    /**
     * Return the index aliases to set to a newly created index for an identifier (eg. product) by catalog.
     *
     * @param string                      $indexIdentifier  An index identifier
     * @param LocalizedCatalog|int|string $localizedCatalog Localized catalog
     *
     * @return string[]
     */
    public function getNewIndexMetadataAliases(string $indexIdentifier, LocalizedCatalog|int|string $localizedCatalog): array
    {
        $catalog = $this->getLocalizedCatalog($localizedCatalog);

        return [
            \sprintf('.entity_%s', $indexIdentifier),
            \sprintf('.catalog_%d', $catalog->getId()),
        ];
    }

    /**
     * Returns settings used during index creation.
     *
     * @return array<mixed>
     */
    public function getCreateIndexSettings(): array
    {
        return [
            'requests.cache.enable' => true,
            'number_of_replicas' => 0,
            'number_of_shards' => $this->getNumberOfShards(),
            'refresh_interval' => self::FULL_REINDEX_REFRESH_INTERVAL,
            'merge.scheduler.max_thread_count' => 1,
            'translog.durability' => self::FULL_REINDEX_TRANSLOG_DURABILITY,
            'codec' => self::CODEC,
            'max_result_window' => $this->getMaxResultWindow(),
            'mapping.total_fields.limit' => self::TOTAL_FIELD_LIMIT,
        ];
    }

    /**
     * Returns settings used when installing an index.
     *
     * @return array<mixed>
     */
    public function getInstallIndexSettings(): array
    {
        return [
            'number_of_replicas' => $this->getNumberOfReplicas(),
            'refresh_interval' => self::DIFF_REINDEX_REFRESH_INTERVAL,
            'translog' => ['durability' => self::DIFF_REINDEX_TRANSLOG_DURABILITY],
        ];
    }

    /**
     * Get number of shards from the configuration.
     */
    public function getNumberOfShards(): int
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_shards');
    }

    /**
     * Get number of replicas from the configuration.
     */
    public function getNumberOfReplicas(): int
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_replicas');
    }

    /**
     * Max number of results per query.
     */
    public function getMaxResultWindow(): int
    {
        return (int) $this->getNumberOfShards() * self::PER_SHARD_MAX_RESULT_WINDOW;
    }

    /**
     * Get maximum shingle diff for an index.
     *
     * @param array<mixed> $analysisSettings Index analysis settings
     */
    public function getMaxShingleDiff(array $analysisSettings): int|false
    {
        $maxShingleDiff = false;
        foreach ($analysisSettings['filter'] ?? [] as $filter) {
            if (($filter['type'] ?? null) === 'shingle') {
                // @codingStandardsIgnoreStart
                $filterDiff = (int) ($filter['max_shingle_size'] ?? self::MAX_SHINGLE_SIZE_DEFAULT)
                    - (int) ($filter['min_shingle_size'] ?? self::MIN_SHINGLE_SIZE_DEFAULT);
                // codingStandardsIgnoreEnd
                $maxShingleDiff = max((int) $maxShingleDiff, $filterDiff) + 1;
            }
        }

        return $maxShingleDiff;
    }

    /**
     * Get maximum ngram diff for an index.
     *
     * @param array<mixed> $analysisSettings Index analysis Settings
     */
    public function getMaxNgramDiff(array $analysisSettings): int|false
    {
        $maxNgramDiff = false;
        foreach ($analysisSettings['filter'] ?? [] as $filter) {
            if (\in_array($filter['type'] ?? null, ['ngram', 'edge_ngram'], true)) {
                $filterDiff = (int) ($filter['max_gram'] ?? self::MAX_NGRAM_SIZE_DEFAULT)
                    - (int) ($filter['min_gram'] ?? self::MIN_NGRAM_SIZE_DEFAULT);

                $maxNgramDiff = max((int) $maxNgramDiff, $filterDiff) + 1;
            }
        }

        return $maxNgramDiff;
    }

    /**
     * Extract original entity from index metadata aliases.
     */
    public function extractEntityFromAliases(Index|IndexTemplate $index): ?string
    {
        $entityType = preg_filter('#^\.entity_(.+)$#', '$1', $index->getAliases(), 1);
        if (!empty($entityType)) {
            if (\is_array($entityType)) {
                $entityType = current($entityType);
            }
        } else {
            $entityType = null;
        }

        return $entityType;
    }

    /**
     * Extract original catalog id from index metadata aliases.
     *
     * @throws \Exception
     */
    public function extractCatalogFromAliases(Index|IndexTemplate $index): ?LocalizedCatalog
    {
        $localizedCatalogId = preg_filter('#^\.catalog_(.+)$#', '$1', $index->getAliases(), 1);
        if (!empty($localizedCatalogId)) {
            if (\is_array($localizedCatalogId)) {
                $localizedCatalogId = current($localizedCatalogId);
            }
            $localizedCatalogId = (int) $localizedCatalogId;
            $catalog = $this->getLocalizedCatalog($localizedCatalogId);
        } else {
            $catalog = null;
        }

        return $catalog;
    }

    /**
     * Check if index name follow the naming convention.
     */
    public function isInternal(Index $index): bool
    {
        return 1 === preg_match("#^{$this->getIndexNamePrefix()}_.*_.*_.*$#", $index->getName());
    }

    /**
     * Check if index has been installed.
     */
    public function isInstalled(Index $index): bool
    {
        $installedAlias = $this->getIndexAliasFromIdentifier($index->getEntityType(), $index->getLocalizedCatalog());

        return \in_array($installedAlias, $index->getAliases(), true);
    }

    /**
     * Check if index is obsolete.
     */
    public function isObsolete(Index $index): bool
    {
        if (!$this->isInternal($index)) {
            return false;
        }

        $timestampPattern = $this->getIndicesSettingsConfigParam('timestamp_pattern');
        $timeBeforeGhost = $this->getIndicesSettingsConfigParam('time_before_ghost');
        preg_match("#^{$this->getIndexNamePrefix()}_.*_.*_([0-9]{8}_[0-9]{6})#", $index->getName(), $creationTime);
        $creationTime = \DateTime::createFromFormat(str_replace(['{', '}'], '', $timestampPattern), $creationTime[1]);
        $currentTime = new \DateTime();

        return ($currentTime->getTimestamp() - $creationTime->getTimestamp()) > $timeBeforeGhost;
    }

    /**
     * Get the index prefix from the configuration.
     */
    protected function getIndexNamePrefix(): string
    {
        return $this->getIndicesSettingsConfigParam('prefix');
    }

    /**
     * Get the ISM prefix from the configuration.
     */
    protected function getIsmPrefix(LocalizedCatalog $localizedCatalog): ?string
    {
        return $this->configurationManager->getScopedConfigValue(
            'gally.ism_settings.prefix',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode()
        );
    }

    /**
     * Get the index template prefix from the configuration.
     */
    protected function getIndexTemplatePrefix(LocalizedCatalog $localizedCatalog): string
    {
        return $this->configurationManager->getScopedConfigValue(
            'gally.index_template_settings.prefix',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode()
        );
    }

    /**
     * Get index name suffix.
     *
     * @param \DateTime $date Date
     */
    private function getIndexNameSuffix(\DateTime $date): string
    {
        /*
         * Generate the suffix of the index name from the current date.
         * e.g : Default pattern "{{YYYYMMdd}}_{{HHmmss}}" is converted to "20160221_123421".
         */
        $indexNameSuffix = $this->getIndicesSettingsConfigParam('timestamp_pattern');

        // Parse pattern to extract datetime tokens.
        $matches = [];
        preg_match_all('/{{([\w]*)}}/', $indexNameSuffix, $matches);

        foreach (array_combine($matches[0], $matches[1]) as $k => $v) {
            // Replace tokens (UTC date used).
            $indexNameSuffix = str_replace($k, $date->format($v), $indexNameSuffix);
        }

        return $indexNameSuffix;
    }

    /**
     * Read config under the path gally.yaml/indices_settings.
     *
     * @param string $configField Configuration field name
     */
    private function getIndicesSettingsConfigParam(string $configField): mixed
    {
        return $this->configurationManager->getScopedConfigValue('gally.indices_settings.' . $configField);
    }

    /**
     * Retrieve the catalog code from object or catalog id.
     *
     * @param int|string|LocalizedCatalog $localizedCatalog The localized catalog or its id or its code
     *
     * @throws \Exception
     */
    private function getCatalogCode(int|string|LocalizedCatalog $localizedCatalog): ?string
    {
        return $this->getLocalizedCatalog($localizedCatalog)->getCode();
    }

    /**
     * Ensure catalog is an object or load it from its id / identifier.
     *
     * @param int|string|LocalizedCatalog $localizedCatalog The catalog or its id or its code
     *
     * @throws \Exception
     */
    private function getLocalizedCatalog(LocalizedCatalog|int|string $localizedCatalog): LocalizedCatalog
    {
        if (!\is_object($localizedCatalog)) {
            $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalog);
        }

        return $localizedCatalog;
    }

    public function getAnalysisSettings(LocalizedCatalog|int|string $localizedCatalog): array
    {
        $language = explode('_', $this->getLocalizedCatalog($localizedCatalog)->getLocale())[0];

        return $this->analysisConfig->get($language);
    }

    public function getIndicesConfig(): array
    {
        // TODO: Implement getIndicesConfig() method.
        return [];
    }

    public function getIndexConfig(string $indexIdentifier): array
    {
        // TODO: Implement getIndexConfig() method.
        return [];
    }

    public function getDynamicIndexSettings(Metadata $metadata, LocalizedCatalog|int|string $localizedCatalog): array
    {
        $settings = [];
        $analysisSettings = $this->getAnalysisSettings($localizedCatalog);

        $shingleDiff = $this->getMaxShingleDiff($analysisSettings);
        $ngramDiff = $this->getMaxNgramDiff($analysisSettings);

        $settings += $shingleDiff ? ['max_shingle_diff' => $shingleDiff] : [];
        $settings += $ngramDiff ? ['max_ngram_diff' => $ngramDiff] : [];

        $settings += ['analysis' => $analysisSettings];

        $complexeSourceField = $this->sourceFieldRepository->getComplexeFields($metadata);
        $settings += ['mapping.nested_fields.limit' => \count($complexeSourceField)];

        // Add default pipeline if any processor are defined.
        $pipeline = $this->pipelineRepository->createByMetadata($metadata);
        if ($pipeline) {
            $settings['default_pipeline'] = $pipeline->getName();
        }

        return $settings;
    }

    /**
     * Get the ISM rollover_after value from the configuration.
     *
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     * @param Metadata|null    $metadata         Optional metadata to check for entity-specific configuration
     */
    public function getIsmRolloverAfter(LocalizedCatalog $localizedCatalog, ?Metadata $metadata = null): ?int
    {
        if ($metadata) {
            $entityCode = $metadata->getEntity();
            $entityConfig = $this->configurationManager->getScopedConfigValue(
                'gally.ism_settings.entities',
                Configuration::SCOPE_LOCALIZED_CATALOG,
                $localizedCatalog->getCode()
            );
            $entityConfig = $entityConfig[$entityCode]['rollover_after'] ?? null;
            if (null !== $entityConfig) {
                return (int) $entityConfig;
            }
        }

        $rolloverAfter = $this->configurationManager->getScopedConfigValue(
            'gally.ism_settings.rollover_after',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode()
        );

        return null !== $rolloverAfter ? (int) $rolloverAfter : null;
    }

    /**
     * Get the ISM delete_after value from the configuration.
     *
     * @param LocalizedCatalog $localizedCatalog Localized catalog
     * @param Metadata|null    $metadata         Optional metadata to check for entity-specific configuration
     */
    public function getIsmDeleteAfter(LocalizedCatalog $localizedCatalog, ?Metadata $metadata = null): ?int
    {
        if ($metadata) {
            $entityCode = $metadata->getEntity();
            $entityConfig = $this->configurationManager->getScopedConfigValue(
                'gally.ism_settings.entities',
                Configuration::SCOPE_LOCALIZED_CATALOG,
                $localizedCatalog->getCode()
            );
            $entityConfig = $entityConfig[$entityCode]['delete_after'] ?? null;
            if (null !== $entityConfig) {
                return (int) $entityConfig;
            }
        }

        $deleteAfter = $this->configurationManager->getScopedConfigValue(
            'gally.ism_settings.delete_after',
            Configuration::SCOPE_LOCALIZED_CATALOG,
            $localizedCatalog->getCode());

        return null !== $deleteAfter ? (int) $deleteAfter : null;
    }
}
