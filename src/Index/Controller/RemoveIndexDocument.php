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

namespace Gally\Index\Controller;

use Gally\Index\Event\AfterBulkDeleteIndexEvent;
use Gally\Index\Repository\Document\DocumentRepositoryInterface;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
class RemoveIndexDocument extends AbstractController
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private IndexRepositoryInterface $indexRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $indexName, Request $request)
    {
        $body = json_decode($request->getContent(), true);
        $ids = $body['document_ids'] ?? [];
        $this->documentRepository->delete($indexName, $ids);

        $index = $this->indexRepository->findByName($indexName);
        if ($index) {
            $this->eventDispatcher->dispatch(new AfterBulkDeleteIndexEvent($index, $ids), AfterBulkDeleteIndexEvent::NAME);
        }
    }
}
