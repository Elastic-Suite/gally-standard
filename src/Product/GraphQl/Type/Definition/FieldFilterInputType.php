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

namespace Gally\Product\GraphQl\Type\Definition;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Gally\Metadata\GraphQl\Type\Definition\Filter\BoolFilterInputType;
use Gally\Metadata\GraphQl\Type\Definition\Filter\EntityFilterInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Service\MetadataManager;
use Gally\Search\Elasticsearch\Builder\Request\Query\Filter\FilterQueryBuilder;
use Gally\Search\GraphQl\Type\Definition\FieldFilterInputType as BaseFieldFilterInputType;
use Psr\Log\LoggerInterface;

class FieldFilterInputType extends BaseFieldFilterInputType
{
    public const NAME = 'ProductFieldFilterInput';

    /**
     * @param EntityFilterInterface[] $availableTypes Filter type
     */
    public function __construct(
        FilterQueryBuilder $filterQueryBuilder,
        private iterable $availableTypes,
        private BoolFilterInputType $boolFilterInputType,
        private MetadataRepository $metadataRepository,
        private MetadataManager $metadataManager,
        private LoggerInterface $logger,
        protected string $nestingSeparator
    ) {
        parent::__construct($availableTypes, $filterQueryBuilder, $nestingSeparator);
        $this->name = self::NAME;
    }

    public function getConfig(): array
    {
        $fields = ['boolFilter' => ['type' => $this->boolFilterInputType]];
        try {
            $metadata = $this->metadataRepository->findByEntity('product');

            foreach ($this->metadataManager->getFilterableSourceFields($metadata) as $filterableField) {
                foreach ($this->availableTypes as $type) {
                    if ($type->supports($filterableField)) {
                        $fields[$type->getGraphQlFieldName($type->getFilterFieldName($filterableField->getCode()))] = ['type' => $type];
                    }
                }
            }
        } catch (InvalidArgumentException $exception) {
            // Metadata product doesn't exist.
            $this->logger->error($exception->getMessage());
        }

        return ['fields' => $fields];
    }
}
