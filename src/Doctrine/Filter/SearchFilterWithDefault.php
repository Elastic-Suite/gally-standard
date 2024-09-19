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

namespace Gally\Doctrine\Filter;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Doctrine\Filter\SearchFilter as GallySearchFilter;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SearchFilterWithDefault extends GallySearchFilter
{
    use SearchFilterTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        IriConverterInterface $iriConverter,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?IdentifiersExtractorInterface $identifiersExtractor = null,
        ?NameConverterInterface $nameConverter = null,
        private string $defaultAlias = 'default',
        private array $defaultValues = [],
    ) {
        parent::__construct(
            $managerRegistry,
            $iriConverter,
            $propertyAccessor,
            $logger,
            $properties,
            $identifiersExtractor,
            $nameConverter
        );
    }

    protected function addWhereByStrategy(string $strategy, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, $values, bool $caseSensitive): void
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = ':' . $queryNameGenerator->generateParameterName($field);

        // Rewrite start : Manage default value.
        $defaultValue = $this->defaultValues[$field] ?? null;
        $aliasedField = "(CASE WHEN $alias.$field IS NULL THEN " .
            "(CASE WHEN $this->defaultAlias.$field IS NOT NULL THEN $this->defaultAlias.$field ELSE '$defaultValue' END) " .
            "ELSE $alias.$field END)";

        if (!$strategy || self::STRATEGY_EXACT === $strategy) {
            $where = [];
            foreach ($values as $value) {
                $parameterName = $queryNameGenerator->generateParameterName($valueParameter);
                $where[] = $queryBuilder->expr()->eq($wrapCase($aliasedField), $wrapCase($parameterName));
                $queryBuilder->setParameter($parameterName, $value);
            }

            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$where));

            return;
        }
        // Rewrite end

        $ors = [];
        $parameters = [];
        foreach ($values as $key => $value) {
            $keyValueParameter = \sprintf('%s_%s', $valueParameter, $key);
            $parameters[$caseSensitive ? $value : strtolower($value)] = $keyValueParameter;

            switch ($strategy) {
                case self::STRATEGY_PARTIAL:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string) $queryBuilder->expr()->concat("'%'", $keyValueParameter, "'%'"))
                    );
                    break;
                case self::STRATEGY_START:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string) $queryBuilder->expr()->concat($keyValueParameter, "'%'"))
                    );
                    break;
                case self::STRATEGY_END:
                    $ors[] = $queryBuilder->expr()->like(
                        $wrapCase($aliasedField),
                        $wrapCase((string) $queryBuilder->expr()->concat("'%'", $keyValueParameter))
                    );
                    break;
                case self::STRATEGY_WORD_START:
                    $ors[] = $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like($wrapCase($aliasedField), $wrapCase((string) $queryBuilder->expr()->concat($keyValueParameter, "'%'"))),
                        $queryBuilder->expr()->like($wrapCase($aliasedField), $wrapCase((string) $queryBuilder->expr()->concat("'% '", $keyValueParameter, "'%'")))
                    );
                    break;
                default:
                    throw new InvalidArgumentException(\sprintf('strategy %s does not exist.', $strategy));
            }
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$ors));
        array_walk($parameters, [$queryBuilder, 'setParameter']);
    }
}
