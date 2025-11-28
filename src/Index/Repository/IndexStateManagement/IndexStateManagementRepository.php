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

use Gally\Index\Api\IndexSettingsInterface;
use Gally\Index\Entity\IndexStateManagement;
use OpenSearch\Client;

class IndexStateManagementRepository implements IndexStateManagementRepositoryInterface
{
    public function __construct(
        private Client $client,
        private IndexSettingsInterface $indexSettings,
    ) {
    }

    public function findAll(): array
    {
        $policies = [];

        try {
            $response = $this->performRequest('GET');
            $prefix = $this->indexSettings->getIsmPrefix();

            foreach ($response['policies'] as $policy) {
                if (str_starts_with($policy['_id'], $prefix . '_')) {
                    $policies[] = $this->mapToEntity($policy);
                }
            }
        } catch (\Exception $e) {
            // Log exception if needed
        }

        return $policies;
    }

    public function findById(string $id): ?IndexStateManagement
    {
        try {
            $response = $this->performRequest('GET', "/{$id}");

            return $this->mapToEntity($response);
        } catch (\Exception $e) {
            // Log exception if needed
        }

        return null;
    }

    public function save(IndexStateManagement $policy): IndexStateManagement
    {
        $policyId = $this->buildPolicyId($policy->getName());
        $body = $this->buildPolicyBody($policy);
        $params = [];

        if (null !== $policy->getSeqNo() && null !== $policy->getPrimaryTerm()) {
            $params['if_seq_no'] = $policy->getSeqNo();
            $params['if_primary_term'] = $policy->getPrimaryTerm();
        }

        $response = $this->performRequest('PUT', "/{$policyId}", $params, $body);

        return $this->mapToEntity($response);
    }

    public function delete(string $id): void
    {
        $this->performRequest('DELETE', "/{$id}");
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

    private function buildPolicyId(string $name): string
    {
        return $this->indexSettings->getIsmPrefix() . '_' . $name;
    }

    /**
     * Convert raw opensearch response to IndexStageManagement object.
     */
    private function mapToEntity(array $data): IndexStateManagement
    {
        $policyData = $data['policy']['policy'] ?? $data['policy'] ?? [];
        $ismTemplate = $policyData['ism_template'][0] ?? [];
        $prefix = preg_quote($this->indexSettings->getIsmPrefix(), '/');

        $deleteAfter = null;
        foreach ($policyData['states'] ?? [] as $state) {
            if ('active' === $state['name']) {
                foreach ($state['transitions'] ?? [] as $transition) {
                    if ('delete' === $transition['state_name'] && isset($transition['conditions']['min_index_age'])) {
                        $deleteAfter = (int) rtrim($transition['conditions']['min_index_age'], 'd');
                        break 2;
                    }
                }
            }
        }

        $ism = new IndexStateManagement(
            name: preg_replace("/{$prefix}_/", '', $data['_id']),
            indexPattern: $ismTemplate['index_patterns'][0] ?? '',
            priority: array_key_exists('priority', $ismTemplate) ? (int) $ismTemplate['priority'] : null,
            description: $policyData['description'] ?? '',
            deleteAfter: $deleteAfter,
        );

        $ism->setId($data['_id']);
        $ism->setSeqNo($data['_seq_no']);
        $ism->setPrimaryTerm($data['_primary_term']);

        return $ism;
    }

    /**
     * Convert IndexStageManagement object to opensearch request.
     *
     * @return array[]
     */
    private function buildPolicyBody(IndexStateManagement $policy): array
    {
        $states = ['active' => ['name' => 'active', 'actions' => [], 'transitions' => []]];

        if (null !== $policy->getDeleteAfter()) {
            $states['active']['transitions'][] = [
                'state_name' => 'delete',
                'conditions' => ['min_index_age' => $policy->getDeleteAfter() . 'd'],
            ];

            $states['delete'] = [
                'name' => 'delete',
                'actions' => [['delete' => new \stdClass()]],
                'transitions' => [],
            ];
        }

        $ismTemplate = ['index_patterns' => [$policy->getIndexPattern()]];

        if (null !== $policy->getPriority()) {
            $ismTemplate['priority'] = $policy->getPriority();
        }

        return [
            'policy' => [
                'description' => $policy->getDescription(),
                'default_state' => 'active',
                'states' => array_values($states),
                'ism_template' => [$ismTemplate],
            ],
        ];
    }
}
