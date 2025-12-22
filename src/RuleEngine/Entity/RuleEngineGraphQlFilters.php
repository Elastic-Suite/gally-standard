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

namespace Gally\RuleEngine\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\Symfony\Action\NotFoundAction;
use Gally\RuleEngine\Controller\RuleEngineGraphQlFiltersController;
use Gally\RuleEngine\Resolver\RuleEngineGraphQlFiltersResolver;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(
            controller: NotFoundAction::class,
            read: false,
            output: false
        ),
        new Post(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            uriTemplate: 'rule_engine_graphql_filters',
            read: false,
            deserialize: false,
            controller: RuleEngineGraphQlFiltersController::class,
            status: 200,
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'rule' => ['type' => 'string'],
                                ],
                            ],
                            'example' => [
                                'rule' => '{"type": "combination", "operator": "all", "value": "true", "children": [{"type": "attribute", "field": "id", "operator": "eq", "attribute_type": "int", "value": 1}]}',
                            ],
                        ],
                    ])
                ),
            ),
        ),
    ],
    graphQlOperations: [
        new Query(
            name: 'get',
            resolver: RuleEngineGraphQlFiltersResolver::class,
            read: false,
            deserialize: false,
            args: ['rule' => ['type' => 'String!']],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
    ],
    paginationEnabled: false,
)]

class RuleEngineGraphQlFilters
{
    private string $id = 'rule_engine_graphql_filters';

    private array $graphQlFilters = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getGraphQlFilters(): array
    {
        return $this->graphQlFilters;
    }

    public function setGraphQlFilters(array $graphQlFilters): void
    {
        $this->graphQlFilters = $graphQlFilters;
    }
}
