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

namespace Gally\Search\GraphQl\Type\Definition\Filter;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use Gally\Configuration\Service\ConfigurationManager;
use Gally\GraphQl\Type\Definition\FilterInterface;
use Gally\Metadata\Entity\SourceField\Type as SourceFieldType;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\DateFormatUtils;
use Gally\Search\Service\ReverseSourceFieldProvider;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class RangeFilterInputType extends InputObjectType implements TypeInterface, FilterInterface
{
    use FilterableFieldTrait;

    public const NAME = 'RangeFilterInput';

    public function __construct(
        private FilterQueryBuilder $filterQueryBuilder,
        private ReverseSourceFieldProvider $reverseSourceFieldProvider,
        private ConfigurationManager $configurationManager,
        private DateFormatUtils $dateUtils,
    ) {
        $this->name = self::NAME;

        parent::__construct($this->getConfig());
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                'field' => ['type' => Type::nonNull(Type::string())],
                FilterOperator::GTE => Type::string(),
                FilterOperator::LTE => Type::string(),
                FilterOperator::GT => Type::string(),
                FilterOperator::LT => Type::string(),
            ],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function validate(string $argName, mixed $inputData, $containerConfig): array
    {
        $errors = $this->validateIsFilterable($inputData['field'], $containerConfig);

        if (\count($inputData) < 2) {
            $errors[] = \sprintf(
                "Filter argument %s: At least '%s', '%s', '%s' or '%s' should be filled.",
                $argName,
                FilterOperator::GT,
                FilterOperator::LT,
                FilterOperator::GTE,
                FilterOperator::LTE,
            );
        }

        if (isset($inputData[FilterOperator::GT]) && isset($inputData[FilterOperator::GTE])) {
            $errors[] = \sprintf(
                "Filter argument %s: Do not use '%s' and '%s' in the same filter.",
                $argName,
                FilterOperator::GT,
                FilterOperator::GTE,
            );
        }

        if (isset($inputData[FilterOperator::LT]) && isset($inputData[FilterOperator::LTE])) {
            $errors[] = \sprintf(
                "Filter argument %s: Do not use '%s' and '%s' in the same filter.",
                $argName,
                FilterOperator::LT,
                FilterOperator::LTE,
            );
        }

        $field = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName(
            $inputData['field'],
            $containerConfig->getMetadata()
        );
        if ($field && SourceFieldType::TYPE_DATE === $field->getType()) {
            $dateFormat = $this->configurationManager->getScopedConfigValue(
                'gally.search_settings.default_date_field_format'
            );
            foreach ($inputData as $operator => $value) {
                if ('field' === $operator) {
                    continue;
                }
                if (!$this->dateUtils->checkDateFormat($value, DateFormatUtils::COMPLETE_DATE_FORMAT)
                    && !$this->dateUtils->checkDateFormat($value, $dateFormat)) {
                    $errors[] = \sprintf(
                        "Filter argument %s: Date format for '%s' is not valid in operator '%s'.",
                        $argName,
                        $value,
                        $operator
                    );
                }
            }
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        $field = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName(
            $inputFilter['field'],
            $containerConfig->getMetadata()
        );
        $dateFormat = $this->configurationManager->getScopedConfigValue(
            'gally.search_settings.default_date_field_format'
        );
        $conditions = [];

        foreach ([FilterOperator::GT, FilterOperator::LT, FilterOperator::GTE, FilterOperator::LTE] as $condition) {
            if (isset($inputFilter[$condition])) {
                if ($field && SourceFieldType::TYPE_DATE === $field->getType()) {
                    if ($this->dateUtils->checkDateFormat($inputFilter[$condition], $dateFormat)) {
                        switch ($condition) {
                            case FilterOperator::LT:
                            case FilterOperator::GTE:
                                $inputFilter[$condition] = $this->dateUtils->getFirstDayOfPeriod($inputFilter[$condition], $dateFormat);
                                break;
                            case FilterOperator::GT:
                            case FilterOperator::LTE:
                                $inputFilter[$condition] = $this->dateUtils->getLastDayOfPeriod($inputFilter[$condition], $dateFormat);
                                break;
                        }
                    }
                }
                $conditions = array_merge($conditions, [$condition => $inputFilter[$condition]]);
            }
        }

        $filterData = [
            $inputFilter['field'] => $conditions,
        ];

        return $this->filterQueryBuilder->create($containerConfig, $filterData);
    }
}
