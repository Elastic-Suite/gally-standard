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

namespace Gally\Doctrine\Filter;


use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\RangeFilterTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter as BaseRangeFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class RangeFilterWithDefault extends AbstractFilter implements RangeFilterInterface
{
    use RangeFilterTrait;
    public function __construct(
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger = null,
        array $properties = null,
        NameConverterInterface $nameConverter = null,
        private string $defaultAlias = 'default',
        private array $defaultValues = [],
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            !\is_array($values)
            || !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($values, $property);
        if (null === $values) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }

        foreach ($values as $operator => $value) {
            $this->addWhere(
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $field,
                $operator,
                $value
            );
        }
    }

    /**
     * Adds the where clause according to the operator.
     */
    protected function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, string $operator, string $value): void
    {
        $valueParameter = $queryNameGenerator->generateParameterName($field);

        $defaultValue = $this->defaultValues[$field];
        $fieldWithDefault = "(CASE WHEN $alias.$field IS NULL THEN " .
            "(CASE WHEN $this->defaultAlias.$field IS NOT NULL THEN $this->defaultAlias.$field ELSE " . (null === $defaultValue ? ':null' : "'$defaultValue'") . ' END) ' .
            "ELSE $alias.$field END)";

        if (null === $defaultValue) {
            $queryBuilder->setParameter('null', null);
        }

        switch ($operator) {
            case self::PARAMETER_BETWEEN:
                $rangeValue = explode('..', $value);

                $rangeValue = $this->normalizeBetweenValues($rangeValue);
                if (null === $rangeValue) {
                    return;
                }

                if ($rangeValue[0] === $rangeValue[1]) {
                    $queryBuilder
                        ->andWhere(sprintf('%s = :%s', $fieldWithDefault, $valueParameter))
                        ->setParameter($valueParameter, $rangeValue[0]);

                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%1$s >= :%2$s_1 AND %1$s <= :%2$s_2', $fieldWithDefault, $valueParameter))
                    ->setParameter(sprintf('%s_1', $valueParameter), $rangeValue[0])
                    ->setParameter(sprintf('%s_2', $valueParameter), $rangeValue[1]);

                break;
            case self::PARAMETER_GREATER_THAN:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s > :%s', $fieldWithDefault, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s >= :%s', $fieldWithDefault, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
            case self::PARAMETER_LESS_THAN:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s < :%s', $fieldWithDefault, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $queryBuilder
                    ->andWhere(sprintf('%s <= :%s', $fieldWithDefault, $valueParameter))
                    ->setParameter($valueParameter, $value);

                break;
        }
    }
}
