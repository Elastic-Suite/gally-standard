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

namespace Gally\RuleEngine\Resolver;

use Gally\RuleEngine\Entity\RuleEngineOperators;
use Gally\RuleEngine\Service\RuleEngineManager;

class RuleEngineOperatorsResolver
{
    public function __construct(
        private RuleEngineManager $ruleEngineManager
    ) {
    }

    public function __invoke($item, array $context): RuleEngineOperators
    {
        return $this->ruleEngineManager->getRuleEngineOperators();
    }
}
