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

namespace Gally\RuleEngine\Service;

/**
 * Additional attribute rule operators, available only in a given context (e.g. recommender rules).
 * They are only exposed to build the rules: the context is responsible for resolving them before the rule engine.
 */
interface ContextOperatorsProviderInterface
{
    public function supports(string $context): bool;

    /**
     * @return array<string, string> labels by operator
     */
    public function getOperators(): array;

    /**
     * @return array<string, string[]> operators by source field type
     */
    public function getOperatorsBySourceFieldType(): array;

    /**
     * @return array<string, array<string, ?string>> value type by operator, by source field type (null: no value)
     */
    public function getOperatorsValueType(): array;
}
