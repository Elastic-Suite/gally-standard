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

namespace Gally\Index\State;

use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Dto\CreateIndexDto;
use Gally\Index\Model\Index;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;

class CreateIndexProcessor implements ProcessorInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private MetadataRepository $metadataRepository,
        private IndexOperation $indexOperation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateIndexDto $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?Index
    {
        $entityType = $data->entityType;
        $localizedCatalogCode = $data->localizedCatalog;

        $metadata = $this->metadataRepository->findOneBy(['entity' => $entityType]);
        if (!$metadata) {
            throw new InvalidArgumentException(\sprintf('Entity type [%s] does not exist', $entityType));
        }
        if (null === $metadata->getEntity()) {
            throw new InvalidArgumentException(\sprintf('Entity type [%s] is not defined', $entityType));
        }

        $catalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogCode);
        if (null === $catalog) {
            throw new InvalidArgumentException(\sprintf('Localized catalog of ID or code [%s] does not exist', $localizedCatalogCode));
        }

        try {
            $index = $this->indexOperation->createEntityIndex($metadata, $catalog);
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            throw new \Exception('An error occurred when creating the index');
        }

        return $index;
    }
}
