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
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class JsonFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        foreach ((array) $value as $key => $val) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            // PostgreSQL JSON field access: ->> extracts the text value of a key
//            $expr = sprintf("%s.%s ->> '%s' = :%s", $alias, $property, $key, $parameterName);
            $expr = $queryBuilder->expr()->eq(
                "JSON_GET_TEXT($alias.$property, '$.$key')",
                ":$parameterName"
            );
            $queryBuilder
                ->andWhere($expr)
                ->setParameter($parameterName, $val);
        }
    }

    public function getDescription(string $resourceClass): array
    {

        $description = [];
        $properties = $this->getProperties();

        foreach ($properties as $property => $propertyData) {
            $description[$property] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
//                'strategy' => $propertyData['strategy'],
            ];
        }

        return $description;

        // todo:  rendre la description generique
//        return [
//            'roles' => [
//                'property' => 'roles',
//                'type' => Type::BUILTIN_TYPE_ARRAY,
//                'required' => false,
//                'swagger' => [
//                    'description' => 'Filter by sub-key of a JSON field',
//                ],
//            ],
//        ];
    }
}
