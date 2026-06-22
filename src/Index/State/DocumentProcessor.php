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

namespace Gally\Index\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Index\Entity\IndexDocument;
use Gally\Index\Event\AfterBulkIndexEvent;
use Gally\Index\Repository\Document\DocumentRepositoryInterface;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private IndexRepositoryInterface $indexRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param IndexDocument $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?IndexDocument
    {
        if (!$operation instanceof DeleteOperationInterface) {
            $this->documentRepository->index($data->getIndexName(), $data->getDocuments());

            $index = $this->indexRepository->findByName($data->getIndexName());
            if ($index) {
                $normalizedData = array_map(fn ($document) => json_decode($document, true), $data->getDocuments());
                $this->eventDispatcher->dispatch(new AfterBulkIndexEvent($index, $normalizedData), AfterBulkIndexEvent::NAME);
            }

            return $data;
        }

        // Remove not managed here
        // @see \Gally\Index\Controller\RemoveIndexDocument::__invoke

        return null;
    }
}
