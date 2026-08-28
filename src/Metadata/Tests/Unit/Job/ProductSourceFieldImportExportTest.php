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

namespace Gally\Metadata\Tests\Unit\Job;

use Doctrine\ORM\EntityManagerInterface;
use Gally\Job\Entity\Job;
use Gally\Job\Tests\Unit\AbstractTestJob;
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Job\Product\ProductSourceFieldExport;
use Gally\Metadata\Job\Product\ProductSourceFieldImport;
use Gally\Product\Tests\Api\GraphQl\ProductSearchAssertionTrait;
use Gally\Search\Entity\Facet\Configuration;
use Gally\Search\Repository\Facet\ConfigurationRepository;

class ProductSourceFieldImportExportTest extends AbstractTestJob
{
    use ProductSearchAssertionTrait;

    private const EXPORT_DIR = __DIR__ . '/../../fixtures/jobs/files/exports/';
    private const PRODUCT_DOCUMENTS = __DIR__ . '/../../fixtures/jobs/product_documents.json';

    /** A select field aggregates under "<code>__value", not under its bare code. */
    private const BRAND_AGGREGATION = 'brand__value';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // The fixtures are loaded once for the whole class, their order is significant.
        static::loadFixture([
            __DIR__ . '/../../fixtures/jobs/jobs.yaml',
            __DIR__ . '/../../fixtures/jobs/source_field.yaml',
            __DIR__ . '/../../fixtures/source_field.yaml',
            __DIR__ . '/../../fixtures/metadata.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        // testBrandBecomesAFacetAfterImport indexes products; drop them so the next class starts clean.
        self::deleteElasticsearchFixtures();
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::copyDirectoryFiles(
            __DIR__ . '/../../fixtures/jobs/files/imports/', self::$jobManager->getJobDirectoryPath(),
        );
    }

    /**
     * Guards the CSV columns against the entities drifting away from them.
     * a new property changes the count and breaks this test.
     */
    public function testCsvColumnsStillCoverEveryPersistedProperty(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $this->assertCount(
            12 /* carried by a CSV column */ + 3 /* never are: id, isSystem, search */,
            $em->getClassMetadata(SourceField::class)->getFieldNames(),
            'A persisted SourceField property was added or removed. If it belongs in the CSV sheet, add'
            . ' it to BASE_CSV_HEADERS, to the setter map in updateSourceFieldFromData(), to'
            . ' AbstractSourceFieldExport::formatCoreSourceFieldData() and to the expected export'
            . ' fixtures. Then fix the count above.',
        );

        $this->assertCount(
            6 /* carried by a CSV column */ + 3 /* never are: id, isRecommendable, isVirtual */,
            $em->getClassMetadata(Configuration::class)->getFieldNames(),
            'A persisted facet Configuration property was added or removed. If it belongs in the CSV'
            . ' sheet, add it to ProductSourceFieldImport::FACET_CONFIGURATION_CSV_FIELDS, to'
            . ' ProductSourceFieldExport::formatSourceFieldLine(), to upsertFacetConfigurationFromData()'
            . ' and to the expected export fixtures. Then fix the count above.',
        );

        // The other direction, so dropping a header without touching the entity fails here too.
        $this->assertCount(12 + 6, ProductSourceFieldExport::CSV_HEADERS);
        $this->assertCount(
            10 + 6,
            ProductSourceFieldImport::CSV_HEADERS,
            'The import declares the export columns minus the read-only "label" and "type".',
        );
    }

    public function testExport(): void
    {
        $job = $this->runJob(1);

        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());
        $this->assertJobCsvEqual($job, self::EXPORT_DIR . 'test_unit_sourcefield_export_initial.csv');

        // Export matches what the back office shows: the two sides are compared to each other, not
        // to literals. "brand" has no stored configuration, so both must resolve the same defaults.
        [$brandConfig] = $this->fetchFacetConfigurations('brand');
        $exportedRow = $this->readExportedRow($job, 'brand');

        $this->assertSame(
            [
                'display_mode' => $brandConfig->getDisplayMode(),
                'coverage_rate' => (string) $brandConfig->getCoverageRate(),
                'max_size' => (string) $brandConfig->getMaxSize(),
                'sort_order' => $brandConfig->getSortOrder(),
                'position' => (string) $brandConfig->getPosition(),
                'boolean_logic' => $brandConfig->getBooleanLogic(),
            ],
            [
                'display_mode' => $exportedRow['display_mode'],
                'coverage_rate' => $exportedRow['coverage_rate'],
                'max_size' => $exportedRow['max_size'],
                'sort_order' => $exportedRow['sort_order'],
                'position' => $exportedRow['position'],
                'boolean_logic' => $exportedRow['boolean_logic'],
            ],
            'The exported facet columns must equal what the back-office facet list resolves for the'
            . ' same source field. A literal on either side would let the two drift apart silently.',
        );
    }

    public function testImportErrors(): void
    {
        $job = $this->runJob(2);

        $this->assertJobHasLogMessage(
            $job,
            [
                'Validation error(s) on line 2: Attribute code is required',
                'Validation error(s) on line 3: Attribute with code "does_not_exist" not found',
                'Validation error(s) on line 4: Invalid boolean value "maybe" for field "is_searchable"',
                'Validation error(s) on line 5: Invalid numeric value "abc" for search weight',
                'Validation error(s) on line 6: Invalid analyzer "fake_analyzer". Allowed values: standard, reference, standard_edge_ngram',
                'Validation error(s) on line 7: Invalid display mode "fake_mode"',
                'Validation error(s) on line 8: Invalid coverage rate "150", it must be a number between 0 and 100',
                'Validation error(s) on line 9: Invalid max size "-1", it must be a positive number',
                'Validation error(s) on line 10: Invalid max size "0", it must be a positive number',
                'Validation error(s) on line 11: Invalid sort order "fake_order"',
                'Validation error(s) on line 12: Invalid boolean logic "XOR", allowed values are "OR" and "AND"',
                'Validation error(s) on line 13: Invalid position "abc"',
            ],
        );

        $this->assertSame(Job::STATUS_FAILED, $job->getStatus());
    }

    /**
     * A system source field only accepts the columns derived from SourceFieldDataValidator::getUpdatableProperties(),
     * plus the facet configuration columns from getAdditionalSystemUpdatableCsvFields().
     * A warning is issued at the end about the non updatable properties in the file, this does not fails the export.
     */
    public function testSystemSourceFieldRestrictions(): void
    {
        $job = $this->runJob(8);

        $this->assertJobHasLogMessage(
            $job,
            'Warning: The following source fields are system attributes: sku. Only the following fields can be changed: '
            . 'weight, is_spellchecked, analyzer, is_spannable, display_mode, coverage_rate, max_size, sort_order, '
            . 'position, boolean_logic. Other columns values will be ignored',
        );

        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());
        $this->assertJobCsvEqual(
            $this->runJob(9),
            self::EXPORT_DIR . 'test_unit_sourcefield_export_after_system_field.csv'
        );
    }

    /**
     * The same restrictions, read from a sheet that spells the booleans 0/1 instead of true/false.
     * parseBooleanValue() accepts both spellings, and every restricted column in this file holds 0,
     * a value an empty() test reads as unset. The warning has to be issued all the same.
     */
    public function testSystemSourceFieldRestrictionsWithNumericBooleans(): void
    {
        $job = $this->runJob(16);

        $this->assertJobHasLogMessage(
            $job,
            'Warning: The following source fields are system attributes: sku. Only the following fields can be changed: '
            . 'weight, is_spellchecked, analyzer, is_spannable, display_mode, coverage_rate, max_size, sort_order, '
            . 'position, boolean_logic. Other columns values will be ignored',
        );

        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());
    }

    /**
     * Checks that non existing facets configuration are not created until necessary.
     */
    public function testFacetConfigurationRules(): void
    {
        $job = $this->runJob(10);

        $this->assertJobHasLogMessage(
            $job,
            [
                'Creating default facet configuration for attribute with code: "color"',
                'Updating default facet configuration for attribute with code: "size"',
                'Skipping facet configuration for the following attributes (source field is not filterable): flag',
                'Skipping facet configuration for the following attributes (all values are default): material',
            ],
        );

        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());
        $this->assertJobCsvEqual(
            $this->runJob(11),
            self::EXPORT_DIR . 'test_unit_sourcefield_export_after_facet.csv'
        );

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        $material = $em->getRepository(SourceField::class)
            ->findByCodeAndMetadataEntity('material', 'product');
        $materialConfig = $em->getRepository(Configuration::class)
            ->findOneBySourceFieldAndDefaultCategory($material);
        $this->assertNull($materialConfig, 'Importing all-default facet values must not create a row.');

        $size = $em->getRepository(SourceField::class)
            ->findByCodeAndMetadataEntity('size', 'product');
        $sizeColumns = $this->fetchStoredFacetColumns($size->getId());

        $this->assertNull($sizeColumns['sort_order'], 'A column equal to the default must be stored as null.');
        $this->assertNull($sizeColumns['boolean_logic'], 'A column equal to the default must be stored as null.');
        $this->assertSame('hidden', $sizeColumns['display_mode']);
        $this->assertSame(80, (int) $sizeColumns['coverage_rate']);
        $this->assertSame(15, (int) $sizeColumns['max_size']);
        $this->assertSame(2, (int) $sizeColumns['position']);
    }

    public function testImportExportRoundTrip(): void
    {
        $job = $this->runJob(6);

        $this->assertJobHasLogMessage($job, 'Extra columns found in the file will be ignored: label, type');

        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());
        $this->assertJobCsvEqual(
            $this->runJob(7),
            self::EXPORT_DIR . 'test_unit_sourcefield_export_after_facet.csv'
        );
    }

    public function testBlankRequiredCellsAreRejected(): void
    {
        $missing = 'Values are required for the following fields: weight, is_searchable, is_filterable,'
            . ' is_sortable, is_spellchecked, is_used_for_rules, is_used_in_autocomplete, is_spannable, analyzer';

        $job = $this->runJob(12);

        $this->assertJobHasLogMessage(
            $job,
            [
                "Validation error(s) on line 2: {$missing}",
                "Validation error(s) on line 3: {$missing}",
                "Validation error(s) on line 4: {$missing}",
            ],
        );

        $this->assertSame(Job::STATUS_FAILED, $job->getStatus());
    }

    public function testFieldMadeFilterableGetsItsFacetConfiguration(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $flagId = $em->getRepository(SourceField::class)
            ->findByCodeAndMetadataEntity('flag', 'product')
            ->getId();

        // No custom facet configuration should exist yet in database
        $this->assertFalse($this->fetchStoredFacetColumns($flagId), 'No configuration row yet.');
        [$flagBefore] = $this->fetchFacetConfigurations('flag');
        $this->assertSame(10, $flagBefore->getMaxSize(), 'Max size is still the default.');

        $job = $this->runJob(14);

        $this->assertJobHasLogMessage($job, 'Creating default facet configuration for attribute with code: "flag"');
        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());

        $this->assertTrue(
            static::getContainer()->get('doctrine')->getManager()
                ->getRepository(SourceField::class)
                ->findByCodeAndMetadataEntity('flag', 'product')
                ->getIsFilterable(),
        );

        // The imported max size is applied, and every column left blank reports its default —
        // this is the same call, and the same resolved values, the back office and the search read.
        [$flagAfter] = $this->fetchFacetConfigurations('flag');
        $this->assertSame(25, $flagAfter->getMaxSize());
        $this->assertSame('auto', $flagAfter->getDisplayMode());
        $this->assertSame(90, $flagAfter->getCoverageRate());
        $this->assertSame('_count', $flagAfter->getSortOrder());
        $this->assertNull($flagAfter->getPosition());
        $this->assertSame('OR', $flagAfter->getBooleanLogic());

        // Only max_size is actually stored: the blank columns stay null so the default keeps
        // applying, rather than being pinned to the value it happens to have today.
        $flagColumns = $this->fetchStoredFacetColumns($flagId);
        $this->assertSame(25, (int) $flagColumns['max_size']);
        $this->assertNull($flagColumns['display_mode']);
        $this->assertNull($flagColumns['coverage_rate']);
        $this->assertNull($flagColumns['sort_order']);
        $this->assertNull($flagColumns['position']);
        $this->assertNull($flagColumns['boolean_logic']);
    }

    /**
     * "brand" starts as non-filterable select field.
     */
    public function testBrandIsNotAFacetBeforeImport(): void
    {
        static::createEntityElasticsearchIndices('product');
        static::loadElasticsearchDocumentFixtures([self::PRODUCT_DOCUMENTS]);

        $this->assertArrayNotHasKey(
            self::BRAND_AGGREGATION,
            $this->fetchProductAggregations('b2c_en'),
            'A non-filterable field must not be offered as a facet.',
        );
    }

    /**
     * Check that brand configuration update is reflected in a real GraphQL call.
     */
    public function testBrandBecomesAFacetAfterImport(): void
    {
        $job = $this->runJob(15);
        $this->assertSame(Job::STATUS_FINISHED, $job->getStatus());

        // is_filterable feeds the index mapping, so the index is rebuilt — the same reindex a real
        // instance needs after changing an attribute.
        static::createEntityElasticsearchIndices('product');
        static::loadElasticsearchDocumentFixtures([self::PRODUCT_DOCUMENTS]);

        $aggregations = $this->fetchProductAggregations('b2c_en');
        $this->assertArrayHasKey(
            self::BRAND_AGGREGATION,
            $aggregations,
            'Importing is_filterable=true must create the facet.',
        );

        $this->assertCount(
            2,
            $aggregations[self::BRAND_AGGREGATION]['options'],
            'max_size=2 must cap the facet options.',
        );
        $this->assertTrue(
            $aggregations[self::BRAND_AGGREGATION]['hasMore'],
            'A capped facet must report that more options exist.',
        );
    }

    /**
     * Fetch facet configurations the way the back office does.
     *
     * @return Configuration[]
     */
    private function fetchFacetConfigurations(string $search): array
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        /** @var ConfigurationRepository $repo */
        $repo = $em->getRepository(Configuration::class);
        $repo->setMetadata($em->getRepository(Metadata::class)->findOneBy(['entity' => 'product']));
        $repo->setSearch($search); // same call ConfigurationCollectionProvider makes for ?search=

        return array_values($repo->findByPositionWithSourceFields([], ['id' => 'ASC']));
    }

    /**
     * Read one source field's row out of the CSV an export job actually produced, keyed by column name.
     *
     * @return array<string, string>
     */
    private function readExportedRow(Job $job, string $code): ?array
    {
        $handle = fopen(self::$jobManager->getAbsoluteJobFilePath($job), 'r');
        $headers = fgetcsv($handle, escape: '\\');

        try {
            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                if ($row[0] === $code) {
                    return array_combine($headers, $row);
                }
            }
        } finally {
            fclose($handle);
        }

        $this->fail("The export contains no row for the source field \"{$code}\".");
    }

    /**
     * Read the stored facet configuration columns for a source field's default category, or false
     * when no row exists. Useful to tell apart a default config from the real overriden config.
     */
    private function fetchStoredFacetColumns(int $sourceFieldId): array|false
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();

        return $connection->fetchAssociative(
            'SELECT display_mode, coverage_rate, max_size, sort_order, position, boolean_logic'
            . ' FROM facet_configuration WHERE source_field_id = ? AND category_id IS NULL',
            [$sourceFieldId],
        );
    }
}
