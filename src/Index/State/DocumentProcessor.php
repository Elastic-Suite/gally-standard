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
use Gally\Index\Model\IndexDocument;
use Gally\Index\Repository\Document\DocumentRepositoryInterface;

class DocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository
    ) {
    }

    /**
     * @param IndexDocument $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?IndexDocument
    {
        if (!$operation instanceof DeleteOperationInterface) {
            $this->documentRepository->index($data->getIndexName(), $data->getDocuments());

            return $data;
        }

        // Remove not managed here
        // @see \Gally\Index\Controller\RemoveIndexDocument::__invoke

        return null;
    }
}
