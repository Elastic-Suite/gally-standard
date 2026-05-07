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
use Gally\Cache\Service\CacheManagerInterface;
use Gally\Metadata\GraphQl\Type\Definition\Filter\BoolFilterInputType;
use Gally\Metadata\GraphQl\Type\Definition\Filter\EntityFilterInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Metadata\Service\MetadataSourceFieldProviderCache;
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
        private LoggerInterface $logger,
        private CacheManagerInterface $cacheManager,
        protected string $nestingSeparator,
        private MetadataSourceFieldProviderCache $metadataSourceFieldProviderCache,
    ) {
        parent::__construct($availableTypes, $filterQueryBuilder, $nestingSeparator);
        $this->name = self::NAME;
    }

    public function getConfig(): array
    {
        $fields = ['boolFilter' => ['type' => $this->boolFilterInputType]];
        try {
            $filterableFields = $this->cacheManager->get(
                'product_filterable_source_fields',
                function (&$tags, &$ttl): array {
                    $metadata = $this->metadataRepository->findByEntity('product');
                    $result = [];
                    foreach ($this->metadataSourceFieldProviderCache->getFilterableSourceFields($metadata) as $filterableField) {
                        foreach ($this->availableTypes as $type) {
                            if ($type->supports($filterableField)) {
                                $result[$filterableField->getCode()] = $type::class;
                                break;
                            }
                        }
                    }

                    return $result;
                },
                [MetadataSourceFieldProviderCache::getEntityTag('product')],
            );

            $typesByClass = [];
            foreach ($this->availableTypes as $type) {
                $typesByClass[$type::class] = $type;
            }

            foreach ($filterableFields as $code => $typeClass) {
                $type = $typesByClass[$typeClass];
                $fields[$type->getGraphQlFieldName($type->getFilterFieldName($code))] = ['type' => $type];
            }
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return ['fields' => $fields];
    }
}
