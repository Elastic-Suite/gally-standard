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
use Gally\Index\Dto\Bulk;
use Gally\Index\Entity\DataStream;
use Gally\Index\Repository\DataStream\DataStreamRepositoryInterface;

class DataStreamProcessor implements ProcessorInterface
{
    public function __construct(
        private DataStreamRepositoryInterface $dataStreamRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof DataStream) {
            throw new \InvalidArgumentException('$data must be a valid DataStream object');
        }

        if ($operation instanceof DeleteOperationInterface || 'delete' == $operation->getName()) {
            $this->dataStreamRepository->delete($data->getName());

            return null;
        }
        if ('bulk' == $operation->getName()) {
            $request = new Bulk\Request();
            $request->addDocuments($data, json_decode($context['args']['input']['data'], true) ?? []);
            $this->runBulkQuery($data, $request);
        }

        return $data;
    }

    protected function runBulkQuery(DataStream $dataStream, Bulk\Request $request): Bulk\Response
    {
        $response = $this->dataStreamRepository->bulk($request);

        if ($response->hasErrors()) {
            $errorMessages = [];
            foreach ($response->aggregateErrorsByReason() as $error) {
                $sampleDocumentIds = implode(', ', \array_slice($error['document_ids'], 0, 10));
                $errorMessages[] = \sprintf(
                    'Bulk %s operation failed %d times in index %s.',
                    $error['operation'],
                    $error['count'],
                    $error['index']
                );
                $errorMessages[] = \sprintf('Error (%s) : %s.', $error['error']['type'], $error['error']['reason']);
                $errorMessages[] = \sprintf('Failed doc ids sample : %s.', $sampleDocumentIds);
            }
            if (!empty($errorMessages)) {
                throw new \InvalidArgumentException(implode(' ', $errorMessages));
            }
        }

        return $response;
    }
}
