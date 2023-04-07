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

namespace Gally\RuleEngine\Service;

use Gally\Cache\Service\CacheManagerInterface;
use Gally\Catalog\Model\LocalizedCatalog;
use Gally\Exception\LogicException;
use Gally\RuleEngine\Model\RuleEngineGraphQlFilters;
use Gally\RuleEngine\Model\RuleEngineOperators;
use Gally\RuleEngine\Service\RuleType\AttributeRule;
use Gally\RuleEngine\Service\RuleType\CombinationRule;
use Gally\RuleEngine\Service\RuleType\RuleTypeInterface;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\GraphQl\FilterManager;

class RuleEngineManager
{
    public const CACHE_TAG_RULE = 'rule_engine_rule';
    public const CACHE_KEY_PREFIX_RULE = 'rule_engine_rule';
    public const CACHE_DEFAULT_TTL = 7200;

    /**
     * @var RuleTypeInterface[]
     */
    private array $ruleTypes;

    public function __construct(
        private FilterManager $filterManager,
        private CacheManagerInterface $cache,
        private iterable $ruleTypeClasses,
        private $cacheTtl = self::CACHE_DEFAULT_TTL,
    ) {
        $this->initRuleTypes();
    }

    public function initRuleTypes(): void
    {
        /** @var RuleTypeInterface $ruleTypeClass */
        foreach ($this->ruleTypeClasses as $ruleTypeClass) {
            $this->addRuleType($ruleTypeClass);
        }
    }

    public function addRuleType(RuleTypeInterface $ruleType): void
    {
        $this->ruleTypes[$ruleType->getRuleType()] = $ruleType;
    }

    /**
     * @@return RuleTypeInterface[]
     */
    public function getRuleTypes(): array
    {
        return $this->ruleTypes;
    }

    public function getRuleEngineOperators(): RuleEngineOperators
    {
        /** @var AttributeRule $attributeRuleType */
        $attributeRuleType = $this->getRuleTypes()[AttributeRule::RULE_TYPE];
        $ruleEngineOperators = new RuleEngineOperators();
        $ruleEngineOperators->setOperators($attributeRuleType->getOperators());
        $ruleEngineOperators->setOperatorsBySourceFieldType($attributeRuleType->getOperatorsBySourceFiledType());
        $ruleEngineOperators->setOperatorsValueType($attributeRuleType->getValueTypeByOperators());

        return $ruleEngineOperators;
    }

    public function getRuleEngineGraphQlFilters(array $rule): RuleEngineGraphQlFilters
    {
        $ruleEngineGraphQlFilters = new RuleEngineGraphQlFilters();
        $ruleEngineGraphQlFilters->setGraphQlFilters($this->transformRuleToGraphQlFilters($rule));

        return $ruleEngineGraphQlFilters;
    }

    public function transformRuleToGraphQlFilters(array $rule): array
    {
        if (!isset($rule['type'])) {
            throw new LogicException('The rule type is not set.');
        }

        if (CombinationRule::RULE_TYPE !== $rule['type']) {
            throw new LogicException('The first rule node should be a combination.');
        }

        return $this->getRuleTypes()[CombinationRule::RULE_TYPE]->transformRuleNodeToGraphQlFilter($rule);
    }

    public function transformRuleNodeToGraphQlFilter(array $rule): array
    {
        if (!isset($rule['type'])) {
            throw new LogicException('The rule type is not set.');
        }

        if (!\in_array($rule['type'], array_keys($this->getRuleTypes()), true)) {
            throw new LogicException("The rule type '{$rule['type']}' does not exist.");
        }

        return $this->getRuleTypes()[$rule['type']]->transformRuleNodeToGraphQlFilter($rule);
    }

    public function transformRuleToGallyFilters(array $rule, ContainerConfigurationInterface $containerConfig, array $filterContext = []): ?QueryInterface
    {
        $cacheKey = $this->getRuleCacheKey($rule, $containerConfig->getLocalizedCatalog());
        $cacheTags = $this->getRuleCacheTags($containerConfig->getLocalizedCatalog());

        return $this->cache->get(
            $cacheKey,
            function (&$tags, &$ttl) use ($rule, $containerConfig, $filterContext): ?QueryInterface {
                $gallyFilters = $this->filterManager->transformToGallyFilters(
                    [$this->transformRuleToGraphQlFilters($rule)],
                    $containerConfig,
                    $filterContext
                );

                return !empty($gallyFilters) ? current($gallyFilters) : null;
            },
            $cacheTags,
            $this->cacheTtl
        );
    }

    public function getRuleCacheKey(array $rule, LocalizedCatalog $localizedCatalog): string
    {
        return sprintf(
            '%s_%s_%s',
            self::CACHE_KEY_PREFIX_RULE,
            $localizedCatalog->getCode(),
            md5(json_encode($rule)),
        );
    }

    public function getRuleCacheTags(?LocalizedCatalog $localizedCatalog = null): array
    {
        $cacheTags = [self::CACHE_TAG_RULE];

        if ($localizedCatalog instanceof LocalizedCatalog) {
            $cacheTags[] = $this->getRuleCacheTagByLocalizedCatalog($localizedCatalog);
        }

        return $cacheTags;
    }

    public function getRuleCacheTagByLocalizedCatalog(LocalizedCatalog $localizedCatalog): string
    {
        return sprintf(
            '%s_%s',
            self::CACHE_TAG_RULE,
            $localizedCatalog->getCode(),
        );
    }
}
