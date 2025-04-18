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

namespace Gally\RuleEngine\Service\RuleType;

use Gally\Exception\LogicException;

class AbstractRuleType
{
    public const RULE_TYPE = 'abstract_rule_type';

    public function getRuleType(): string
    {
        if (self::RULE_TYPE === $this::RULE_TYPE) {
            throw new LogicException(\sprintf('The constant RULE_TYPE is not defined in the class %s', static::class));
        }

        return $this::RULE_TYPE;
    }
}
