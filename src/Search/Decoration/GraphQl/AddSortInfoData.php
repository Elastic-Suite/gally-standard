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

namespace Gally\Search\Decoration\GraphQl;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Metadata\Repository\MetadataRepository;
use Gally\Search\State\Paginator;
use Gally\Search\Model\Document;
use Gally\Search\Service\ReverseSourceFieldProvider;

class AddSortInfoData implements ProcessorInterface
{
    public function __construct(
        private iterable $sortOrderProviders,
        private ReverseSourceFieldProvider $reverseSourceFieldProvider,
        private MetadataRepository $metadataRepository,
        private ProcessorInterface $decorated,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        if (Document::class === $operation->getClass() || is_subclass_of($operation->getClass(), Document::class)) {
            $metadata = $this->metadataRepository->findByEntity($context['args']['entityType']);
            /** @var Paginator $data */
            $sortOrders = $data->getCurrentSortOrders();
            if (!empty($sortOrders)) {
                $result['sortInfo'] = ['current' => []];
                // TODO handle correctly or filter out \Gally\Search\Elasticsearch\Builder\Request\SortOrder\Script.
                foreach ($sortOrders as $sortOrder) {
                    $sourceField = $this->reverseSourceFieldProvider->getSourceFieldFromFieldName($sortOrder->getField(), $metadata);
                    if ($sourceField) {
                        $fieldName = $sourceField->getCode();
                        foreach ($this->sortOrderProviders as $sortOrderProvider) {
                            if ($sortOrderProvider->supports($sourceField)) {
                                $fieldName = $sortOrderProvider->getSortOrderField($sourceField);
                            }
                        }
                    } else {
                        $fieldName = $sortOrder->getField();
                    }

                    $result['sortInfo']['current'][] = ['field' => $fieldName, 'direction' => $sortOrder->getDirection()];
                    break;
                }
            }
        }

        return $result;
    }
}
