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

namespace Gally\Metadata\GraphQl\Type\Definition\Filter;

use Gally\Configuration\Service\ConfigurationManager;
use Gally\Metadata\Entity\SourceField;
use Gally\Search\Constant\FilterOperator;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\Elasticsearch\Request\ContainerConfigurationInterface;
use Gally\Search\Elasticsearch\Request\QueryFactory;
use Gally\Search\Elasticsearch\Request\QueryInterface;
use Gally\Search\Service\DateFormatUtils;
use GraphQL\Type\Definition\Type;

class DateTypeFilterInputType extends AbstractFilter
{
    public const NAME = 'EntityDateTypeFilterInput';

    public string $name = self::NAME;

    public function __construct(
        FilterQueryBuilder $filterQueryBuilder,
        QueryFactory $queryFactory,
        private ConfigurationManager $configurationManager,
        private DateFormatUtils $dateUtils,
        string $nestingSeparator,
    ) {
        parent::__construct($filterQueryBuilder, $queryFactory, $nestingSeparator);
    }

    public function supports(SourceField $sourceField): bool
    {
        return \in_array(
            $sourceField->getType(),
            [
                SourceField\Type::TYPE_DATE,
            ], true
        );
    }

    public function getConfig(): array
    {
        return [
            'fields' => [
                FilterOperator::EQ => Type::string(),
                FilterOperator::IN => Type::listOf(Type::string()),
                FilterOperator::GTE => Type::string(),
                FilterOperator::GT => Type::string(),
                FilterOperator::LT => Type::string(),
                FilterOperator::LTE => Type::string(),
                FilterOperator::EXIST => Type::boolean(),
            ],
        ];
    }

    public function validate(string $argName, mixed $inputData, ContainerConfigurationInterface $containerConfig): array
    {
        $errors = [];

        if (empty($inputData)) {
            $errors[] = \sprintf(
                "Filter argument %s: At least '%s', '%s', '%s', '%s', '%s', '%s' or '%s' should be filled.",
                $argName,
                FilterOperator::EQ,
                FilterOperator::IN,
                FilterOperator::GTE,
                FilterOperator::GT,
                FilterOperator::LT,
                FilterOperator::LTE,
                FilterOperator::EXIST,
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

        $dateFormat = $this->configurationManager->getScopedConfigValue(
            'gally.search_settings.default_date_field_format'
        );
        foreach ($inputData as $operator => $value) {
            foreach (\is_array($value) ? $value : [$value] as $dateStr) {
                if (!$this->dateUtils->checkDateFormat($dateStr, DateFormatUtils::COMPLETE_DATE_FORMAT)
                    && !$this->dateUtils->checkDateFormat($dateStr, $dateFormat)) {
                    $errors[] = \sprintf(
                        "Filter argument %s: Date format for '%s' is not valid in operator '%s'.",
                        $argName,
                        $dateStr,
                        $operator
                    );
                }
            }
        }

        return $errors;
    }

    public function transformToGallyFilter(array $inputFilter, ContainerConfigurationInterface $containerConfig, array $filterContext = []): QueryInterface
    {
        if (isset($inputFilter[FilterOperator::IN])) {
            $queries = [];
            foreach ($inputFilter[FilterOperator::IN] as $value) {
                $queries[] = $this->transformToGallyFilter(
                    ['field' => $inputFilter['field'], FilterOperator::EQ => $value],
                    $containerConfig,
                    $filterContext
                );
            }

            return $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $queries]);
        }

        if (isset($inputFilter[FilterOperator::EQ])) {
            $inputFilter[FilterOperator::LTE] = $inputFilter[FilterOperator::EQ];
            $inputFilter[FilterOperator::GTE] = $inputFilter[FilterOperator::EQ];
            unset($inputFilter[FilterOperator::EQ]);
        }

        // Convert date format if necessary to complete date.
        $dateFormat = $this->configurationManager->getScopedConfigValue(
            'gally.search_settings.default_date_field_format'
        );
        foreach ($inputFilter as $operator => $value) {
            if ($this->dateUtils->checkDateFormat($value, $dateFormat)) {
                switch ($operator) {
                    case FilterOperator::LT:
                    case FilterOperator::GTE:
                        $inputFilter[$operator] = $this->dateUtils->getFirstDayOfPeriod($value, $dateFormat);
                        break;
                    case FilterOperator::GT:
                    case FilterOperator::LTE:
                        $inputFilter[$operator] = $this->dateUtils->getLastDayOfPeriod($value, $dateFormat);
                        break;
                }
            }
        }

        return parent::transformToGallyFilter($inputFilter, $containerConfig, $filterContext);
    }
}
