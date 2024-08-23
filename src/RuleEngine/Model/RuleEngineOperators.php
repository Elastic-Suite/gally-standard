<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\RuleEngine\Model;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use Gally\RuleEngine\Controller\RuleEngineOperatorsController;
use Gally\RuleEngine\Resolver\RuleEngineOperatorsResolver;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
            uriTemplate: 'rule_engine_operators',
            read: false,
            deserialize: false,
            serialize: true,
            controller: RuleEngineOperatorsController::class
        ),
    ],
    graphQlOperations: [
        new Query(
            name: 'get',
            resolver: RuleEngineOperatorsResolver::class,
            read: false,
            deserialize: false,
            args: [],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"
        ),
    ],
    paginationEnabled: false
)]

class RuleEngineOperators
{
    private string $id = 'rule_engine_operators';

    private array $operators = [];
    private array $operatorsBySourceFieldType = [];
    private array $operatorsValueType = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getOperators(): ?array
    {
        return $this->operators;
    }

    public function setOperators(array $operators): self
    {
        $this->operators = $operators;

        return $this;
    }

    public function getOperatorsBySourceFieldType(): ?array
    {
        return $this->operatorsBySourceFieldType;
    }

    public function setOperatorsBySourceFieldType(array $operatorsBySourceFieldType): self
    {
        $this->operatorsBySourceFieldType = $operatorsBySourceFieldType;

        return $this;
    }

    public function getOperatorsValueType(): array
    {
        return $this->operatorsValueType;
    }

    public function setOperatorsValueType(array $operatorsValueType): void
    {
        $this->operatorsValueType = $operatorsValueType;
    }
}
