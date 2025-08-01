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

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class JsonFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        // Is it an array ?
        $values = \is_array($value) ? $value : [$value];
        $values = array_values(array_filter($values, static fn ($v) => \is_string($v) && '' !== trim($v)));

        if (0 === \count($values)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // Create individual parameters for array[:p1, :p2, ...]
        $paramPlaceholders = [];
        foreach ($values as $i => $val) {
            $paramName = $queryNameGenerator->generateParameterName($property . $i);
            $paramPlaceholders[] = ':' . $paramName;
            $queryBuilder->setParameter($paramName, $val); // pas Connection::PARAM_STR_ARRAY
        }

        $arraySql = \sprintf('ARRAY(%s)', implode(', ', $paramPlaceholders));

        // Generate SQL expression "JSONB_EXISTS_ANY"
        $expr = \sprintf('JSONB_EXISTS_ANY(%s.%s, %s) = true', $alias, $property, $arraySql);

        $queryBuilder->andWhere($expr);
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];
        $properties = $this->getProperties();

        foreach ($properties as $property => $propertyData) {
            $description[$this->normalizePropertyName($property)] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'is_collection' => false,
                'description' => 'Filter JSON fields containing arrays of values.',
            ];

            $description[$this->normalizePropertyName($property) . '[]'] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'is_collection' => true,
                'description' => 'Filter JSON fields containing arrays of values.',
            ];
        }

        return $description;
    }
}
