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

namespace Gally\Search\GraphQl\Type\Definition;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use Gally\GraphQl\Type\Definition\FilterInterface;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use GraphQL\Type\Definition\InputObjectType;

class FieldFilterInputType extends InputObjectType implements TypeInterface, FilterInterface
{
    public const NAME = 'FieldFilterInput';

    /**
     * @param FilterInterface[] $availableTypes
     */
    public function __construct(
        private iterable $availableTypes,
        private FilterQueryBuilder $filterQueryBuilder,
        protected string $nestingSeparator,
    ) {
        $this->name = self::NAME;

        parent::__construct($this->getConfig());
    }

    public function getConfig(): array
    {
        return [
            'fields' => array_map(
                fn ($filterType) => ['type' => $filterType],
                $this->availableTypes
            ),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function validate(string $argName, mixed $inputData, ContainerConfigurationInterface $containerConfiguration): array
    {
        $errors = [];
        $config = $this->getConfig();

        foreach ($inputData as $filterInputData) {
            foreach ($filterInputData as $filterType => $data) {
                if (str_contains($filterType, '.')) {
                    // Api platform automatically replace nesting separator by '.',
                    // but it keeps the value with nesting separator. In order to avoid applying
                    // the filter twice, we have to skip the one with the '.'.
                    continue;
                }

                if (!\array_key_exists($filterType, $config['fields'])) {
                    $errors[] = "The filter type {$filterType} is not valid.";
                    continue;
                }

                /** @var FilterInterface $type */
                $type = $config['fields'][$filterType]['type'];
                $errors = array_merge($errors, $type->validate($filterType, $data, $containerConfiguration));
            }
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        $filters = [];
        $config = $this->getConfig();

        foreach ($inputFilter as $filterType => $data) {
            if (str_contains($filterType, '.')) {
                // Api platform automatically replace nesting separator by '.',
                // but it keeps the value with nesting separator. In order to avoid applying
                // the filter twice, we have to skip the one with the '.'.
                continue;
            }

            /** @var FilterInterface $type */
            $type = $config['fields'][$filterType]['type'];
            if (!\array_key_exists('field', $data)) {
                $data['field'] = $filterType;
            }

            $data['field'] = str_replace($this->nestingSeparator, '.', $data['field']);
            $filters[] = $type->transformToGallyFilter($data, $containerConfig, $filterContext);
        }

        return $this->filterQueryBuilder->create($containerConfig, $filters);
    }
}
