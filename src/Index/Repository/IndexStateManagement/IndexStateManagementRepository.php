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

namespace Gally\Index\Repository\IndexStateManagement;

use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\IndexStateManagement;
use Gally\Metadata\Entity\Metadata;
use OpenSearch\Client;

class IndexStateManagementRepository implements IndexStateManagementRepositoryInterface
{
    public function __construct(
        private Client $client,
        private IndexSettingsInterface $indexSettings,
    ) {
    }

    public function createForEntity(
        Metadata $metadata,
        LocalizedCatalog $localizedCatalog,
    ): IndexStateManagement {
        return $this->create(
            $metadata->getEntity(),
            $localizedCatalog,
            [$this->indexSettings->getIndexAliasFromIdentifier($metadata->getEntity(), $localizedCatalog)],
            $this->indexSettings->getIsmRolloverAfter($localizedCatalog, $metadata),
            $this->indexSettings->getIsmDeleteAfter($localizedCatalog, $metadata)
        );
    }

    public function create(
        string $identifier,
        LocalizedCatalog $localizedCatalog,
        array $indexPatterns,
        ?int $rolloverAfter,
        ?int $deleteAfter,
        ?string $description = null,
        ?int $priority = null,
    ): IndexStateManagement {
        $ism = new IndexStateManagement(
            $identifier,
            $indexPatterns,
            $priority,
            $description,
            $rolloverAfter,
            $deleteAfter
        );

        $ism->setLocalizedCatalog($localizedCatalog);

        return $this->save($ism);
    }

    public function findByMetadata(Metadata $metadata, LocalizedCatalog $localizedCatalog): ?IndexStateManagement
    {
        return $this->findByName($metadata->getEntity(), $localizedCatalog);
    }

    public function findByName(string $name, LocalizedCatalog $localizedCatalog): ?IndexStateManagement
    {
        try {
            $policyId = $this->indexSettings->createIsmNameFromIdentifier($name, $localizedCatalog);
            $response = $this->performRequest('GET', "/{$policyId}");

            return $this->createFromResponse($response, $localizedCatalog);
        } catch (\Exception $e) {
            // Log exception if needed
        }

        return null;
    }

    public function findAll(LocalizedCatalog $localizedCatalog): array
    {
        $policies = [];

        try {
            $response = $this->performRequest('GET');
            $prefix = $this->indexSettings->createIsmNameFromIdentifier('', $localizedCatalog);

            foreach ($response['policies'] as $policy) {
                if (str_starts_with($policy['_id'], $prefix)) {
                    $policies[] = $this->createFromResponse($policy, $localizedCatalog);
                }
            }
        } catch (\Exception $e) {
            // Log exception if needed
        }

        return $policies;
    }

    public function update(IndexStateManagement $policy): IndexStateManagement
    {
        return $this->save($policy);
    }

    public function delete(string $id): void
    {
        $this->performRequest('DELETE', "/{$id}");
    }

    private function save(IndexStateManagement $policy): IndexStateManagement
    {
        $policyId = $this->indexSettings->createIsmNameFromIdentifier($policy->getName(), $policy->getLocalizedCatalog());
        $states = ['active' => ['name' => 'active', 'actions' => [], 'transitions' => []]];

        if (null !== $policy->getRolloverAfter()) {
            $states['active']['transitions'][] = [
                'state_name' => 'rollover',
                'conditions' => ['min_index_age' => $policy->getRolloverAfter() . 'd'],
            ];

            $states['rollover'] = [
                'name' => 'rollover',
                'actions' => [['rollover' => new \stdClass()]],
                'transitions' => [],
            ];

            if (null !== $policy->getDeleteAfter()) {
                $states['rollover']['transitions'][] = [
                    'state_name' => 'delete',
                    'conditions' => ['min_index_age' => $policy->getDeleteAfter() . 'd'],
                ];
            }
        } elseif (null !== $policy->getDeleteAfter()) {
            $states['active']['transitions'][] = [
                'state_name' => 'delete',
                'conditions' => ['min_index_age' => $policy->getDeleteAfter() . 'd'],
            ];
        }

        if (null !== $policy->getDeleteAfter()) {
            $states['delete'] = [
                'name' => 'delete',
                'actions' => [['delete' => new \stdClass()]],
                'transitions' => [],
            ];
        }

        $params = [];
        if (null !== $policy->getSeqNo() && null !== $policy->getPrimaryTerm()) {
            $params['if_seq_no'] = $policy->getSeqNo();
            $params['if_primary_term'] = $policy->getPrimaryTerm();
        }

        // Create an index template to force lock and ism index to be created with a valid replica number value.
        $this->client->indices()->putIndexTemplate([
            'name' => 'ism_index_template',
            'body' => [
                'index_patterns' => [
                    '.opendistro-job-scheduler-lock',
                    '.opendistro-ism-config',
                ],
                'priority' => 100,
                'template' => [
                    'settings' => [
                        'number_of_shards' => '1',
                        'number_of_replicas' => $this->indexSettings->getNumberOfReplicas(),
                    ],
                ],
            ],
        ]);
        $reponse = $this->performRequest(
            'PUT',
            "/{$policyId}",
            $params,
            [
                'policy' => [
                    'description' => $policy->getDescription(),
                    'default_state' => 'active',
                    'states' => array_values($states),
                    'ism_template' => array_filter([
                        [
                            'index_patterns' => $policy->getIndexPatterns(),
                            'priority' => $policy->getPriority(),
                        ],
                    ]),
                ],
            ]
        );

        return $this->createFromResponse($reponse, $policy->getLocalizedCatalog());
    }

    private function performRequest(string $method, string $uri = '', array $params = [], $body = null, array $options = [])
    {
        $response = $this->client->transport->performRequest(
            $method,
            '/_plugins/_ism/policies' . $uri,
            $params,
            $body,
            $options
        );

        return $this->client->transport->resultOrFuture($response, $options);
    }

    /**
     * Convert raw opensearch response to IndexStageManagement object.
     */
    private function createFromResponse(array $data, LocalizedCatalog $localizedCatalog): IndexStateManagement
    {
        $policyData = $data['policy']['policy'] ?? $data['policy'] ?? [];
        $ismTemplate = $policyData['ism_template'][0] ?? [];
        $prefix = $this->indexSettings->createIsmNameFromIdentifier('', $localizedCatalog);

        $rolloverAfter = null;
        $deleteAfter = null;
        foreach ($policyData['states'] ?? [] as $state) {
            foreach ($state['transitions'] ?? [] as $transition) {
                if ('rollover' === $transition['state_name'] && isset($transition['conditions']['min_index_age'])) {
                    $rolloverAfter = (int) rtrim($transition['conditions']['min_index_age'], 'd');
                }
                if ('delete' === $transition['state_name'] && isset($transition['conditions']['min_index_age'])) {
                    $deleteAfter = (int) rtrim($transition['conditions']['min_index_age'], 'd');
                }
            }
        }

        $ism = new IndexStateManagement(
            name: preg_replace("/{$prefix}/", '', $data['_id']),
            indexPatterns: $ismTemplate['index_patterns'] ?? [],
            priority: \array_key_exists('priority', $ismTemplate) ? (int) $ismTemplate['priority'] : null,
            description: $policyData['description'] ?? '',
            rolloverAfter: $rolloverAfter,
            deleteAfter: $deleteAfter,
        );

        $ism->setId($data['_id']);
        $ism->setSeqNo($data['_seq_no']);
        $ism->setPrimaryTerm($data['_primary_term']);
        $ism->setLocalizedCatalog($localizedCatalog);

        return $ism;
    }
}
