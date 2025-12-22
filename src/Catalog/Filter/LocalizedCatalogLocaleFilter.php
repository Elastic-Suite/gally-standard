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

namespace Gally\Catalog\Filter;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Doctrine\Filter\SearchFilter as GallySearchFilter;
use Gally\Exception\LogicException;

/**
 * Filters the collection on localizedCatalogs and the locale related to localizedCatalogs.
 * The property class that store the locale must be passed on filter properties via the key locale_property.
 * For example:
 * #[ApiFilter(LocalizedCatalogLocaleFilter::class, properties: ['localizedCatalogs.id' => ['locale_property' => 'locales.locale']])]
 * Here locales.locale is the property where locales are stored.
 */
class LocalizedCatalogLocaleFilter extends GallySearchFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $values = $this->normalizeValues((array) $value, $property);
        $values = array_map([$this, 'getIdFromValue'], $values);
        if (empty($values)) {
            return;
        }

        $propertiesToFilter = $this->properties[$property] ?? [];
        $localeProperty = $propertiesToFilter['locale_property'] ?? null;

        if (null === $localeProperty) {
            throw new LogicException("The 'locale_property' is not set in the filter configuration.");
        }

        [$localizedCatalogAlias] = $this->addJoinsForNestedProperty(
            $property,
            $alias,
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            Join::LEFT_JOIN
        );

        [$localeAlias, $localeField] = $this->addJoinsForNestedProperty(
            $localeProperty,
            $alias,
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            Join::LEFT_JOIN
        );

        $queryBuilder->setParameter('localizedCatalogs', $values);

        $queryBuilder->leftJoin(
            LocalizedCatalog::class,
            'lcl',
            'WITH',
            \sprintf('lcl.locale = %s.%s', $localeAlias, $localeField)
        );

        $queryBuilder
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->in('lcl.id', ':localizedCatalogs'),
                $queryBuilder->expr()->in($localizedCatalogAlias . '.id', ':localizedCatalogs'),
            ));
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description[$this->normalizePropertyName($property)] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'strategy' => self::STRATEGY_EXACT,
                'is_collection' => false,
                'description' => 'Filter on localized catalogs and locales related to localized catalogs.',
            ];

            $description[$this->normalizePropertyName($property) . '[]'] = [
                'property' => $property,
                'type' => 'array',
                'required' => false,
                'strategy' => self::STRATEGY_EXACT,
                'is_collection' => true,
                'description' => 'Filter on localized catalogs and locales related to localized catalogs.',
            ];
        }

        return $description;
    }
}
