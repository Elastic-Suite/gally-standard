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

namespace Gally\Tracker\Command;

use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Dto\Bulk;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\Elasticsearch\Adapter;
use Gally\Search\Elasticsearch\Adapter\Common\Response\AggregationInterface;
use Gally\Search\Elasticsearch\Adapter\Common\Response\BucketValueInterface;
use Gally\Search\Elasticsearch\Builder\Request\SimpleRequestBuilder;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Debug/validation command: runs the tracking_session_aggregator live _search (see
 * SessionAggregatorAggregationProvider) against tracking_event and bulk-indexes the result into
 * tracking_session, so the aggregation can be eyeballed before it gets ported to an OpenSearch
 * Transform. Not meant as a production ingestion path (see the "container configuration" vs
 * "Transform" discussion): no incremental/continuous execution, no alias blue-green swap, it just
 * (re)creates the index and (re)installs it.
 */
#[AsCommand(name: 'gally:tracker:build-sessions')]
class BuildTrackingSessionIndexCommand extends Command
{
    public function __construct(
        private MetadataRepository $metadataRepository,
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ContainerConfigurationProvider $containerConfigurationProvider,
        private SimpleRequestBuilder $requestBuilder,
        private Adapter $adapter,
        private IndexOperation $indexOperation,
        private IndexRepositoryInterface $indexRepository,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Aggregate tracking_event into tracking_session documents for one localized catalog and index them (validation step before the OpenSearch Transform)')
            ->addArgument('localizedCatalog', InputArgument::REQUIRED, 'Localized catalog code')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max number of sessions to index', 100)
            ->addOption('dump-raw', null, InputOption::VALUE_NONE, 'Dump the raw aggregation response of the first session instead of indexing (to check the response shape)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        $localizedCatalog = $this->localizedCatalogRepository->findByCodeOrId($input->getArgument('localizedCatalog'));
        $limit = (int) $input->getOption('limit');

        $eventMetadata = $this->metadataRepository->findByEntity('tracking_event');
        $containerConfig = $this->containerConfigurationProvider->get($eventMetadata, $localizedCatalog, 'tracking_session_aggregator');

        $response = $this->adapter->search($this->requestBuilder->create($containerConfig, 0, 0));
        $aggregations = $response->getAggregations();

        if (!isset($aggregations['session_id'])) {
            $ui->error('No "session_id" aggregation in the response, check the request/mapping.');

            return Command::FAILURE;
        }

        $sessionBuckets = $aggregations['session_id']->getValues();

        if ($input->getOption('dump-raw')) {
            $first = array_key_first($sessionBuckets);
            $ui->writeln(sprintf('Raw child aggregations for session "%s":', $first));
            $ui->writeln(json_encode(
                array_map(
                    fn (AggregationInterface $agg) => $agg->getValues(),
                    $sessionBuckets[$first]?->getChildAggregation() ?? []
                ),
                \JSON_PRETTY_PRINT
            ));

            return Command::SUCCESS;
        }

        $documents = [];
        foreach ($sessionBuckets as $sessionBucket) {
            if (\count($documents) >= $limit) {
                break;
            }
            $documents[] = $this->buildSessionDocument($sessionBucket, $localizedCatalog->getCode());
        }

        $ui->writeln(sprintf('%d session(s) aggregated.', \count($documents)));

        if (empty($documents)) {
            return Command::SUCCESS;
        }

        $sessionMetadata = $this->metadataRepository->findByEntity('tracking_session');
        $index = $this->indexOperation->createEntityIndex($sessionMetadata, $localizedCatalog);
        $this->indexOperation->installIndexByName($index->getName());

        $bulkRequest = new Bulk\Request();
        $bulkRequest->addDocuments($index, $documents);
        $bulkResponse = $this->indexRepository->bulk($bulkRequest);
        $this->indexRepository->refresh($index->getName());

        $ui->writeln(sprintf(
            'Indexed into "%s": %d success, %d error(s).',
            $index->getName(),
            $bulkResponse->countSuccess(),
            $bulkResponse->countErrors()
        ));

        if ($bulkResponse->hasErrors()) {
            $ui->warning(json_encode($bulkResponse->aggregateErrorsByReason(), \JSON_PRETTY_PRINT));
        }

        return Command::SUCCESS;
    }

    private function buildSessionDocument(BucketValueInterface $sessionBucket, string $localizedCatalogCode): array
    {
        $sessionUid = $sessionBucket->getKey();
        $children = $sessionBucket->getChildAggregation();

        return [
            'id' => $sessionUid,
            '@timestamp' => $this->readMetricValue($children, 'end_time'),
            'localized_catalog_code' => $localizedCatalogCode,
            'start_time' => $this->readMetricValue($children, 'start_time'),
            'end_time' => $this->readMetricValue($children, 'end_time'),
            'group_id' => $this->readFirstTermKey($children, 'group_id'),
            'session_uid' => $sessionUid,
            'session_vid' => $this->readFirstTermKey($children, 'visitor_id'),
            'views' => $this->readGroupedTerms($children, 'views'),
            'cart' => $this->readGroupedTerms($children, 'cart'),
            'order' => $this->readGroupedTerms($children, 'order'),
            'searches' => $this->readSearches($children),
        ];
    }

    /**
     * @param AggregationInterface[] $children
     */
    private function readMetricValue(array $children, string $name): ?string
    {
        $values = $children[$name]?->getValues() ?? [];

        return $values['value_as_string'] ?? (isset($values['value']) ? (string) $values['value'] : null);
    }

    /**
     * @param AggregationInterface[] $children
     */
    private function readFirstTermKey(array $children, string $name): ?string
    {
        $buckets = $children[$name]?->getValues() ?? [];
        $first = reset($buckets);

        return $first instanceof BucketValueInterface ? (string) $first->getKey() : null;
    }

    /**
     * @param AggregationInterface[] $children
     */
    private function readGroupedTerms(array $children, string $name): array
    {
        $buckets = $children[$name]?->getValues() ?? [];
        $result = [];

        foreach ($buckets as $bucket) {
            $itemBuckets = $bucket->getChildAggregation()['items']?->getValues() ?? [];
            $items = array_map(fn (BucketValueInterface $item) => (string) $item->getKey(), $itemBuckets);
            $result[] = [
                'metadata_code' => $bucket->getKey(),
                'count' => \count($items),
                'items' => array_values($items),
            ];
        }

        return $result;
    }

    /**
     * @param AggregationInterface[] $children
     */
    private function readSearches(array $children): array
    {
        $buckets = $children['searches']?->getValues() ?? [];
        $result = [];

        foreach ($buckets as $bucket) {
            // search_query_root is a reverseNestedBucket with no "buckets" of its own: unlike a
            // terms bucket, Gally's response AggregationBuilder does not recurse into its children,
            // they come back as raw, still-wrapped (nested-envelope) ES JSON -- unwrap by hand.
            $searchQueryRoot = $bucket->getChildAggregation()['search_query_root']?->getValues() ?? [];
            $resultsCount = $this->unwrapSameKey('results_count', $searchQueryRoot['results_count'] ?? []);

            $result[] = [
                'metadata_code' => 'product',
                'query' => (string) $bucket->getKey(),
                'results_count' => (int) ($resultsCount['value'] ?? 0),
            ];
        }

        return $result;
    }

    private function unwrapSameKey(string $name, array $raw): array
    {
        while (\array_key_exists($name, $raw) && \is_array($raw[$name])) {
            $raw = $raw[$name];
        }

        return $raw;
    }
}
