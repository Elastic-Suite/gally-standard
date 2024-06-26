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

namespace Gally\Index\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Index\Dto\CreateIndexInput;
use Gally\Index\Model\Index;
use Gally\Index\Service\IndexOperation;
use Gally\Metadata\Repository\MetadataRepository;
use Psr\Log\LoggerInterface;

class CreateIndexInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private MetadataRepository $metadataRepository,
        private IndexOperation $indexOperation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param object|array<mixed> $data    object on normalize / array on denormalize
     * @param string              $to      target class
     * @param array<mixed>        $context context
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        // in the case of an input, the value given here is an array (the JSON decoded).
        // if it's an index we transformed the data already
        if ($data instanceof Index) {
            return false;
        }

        return Index::class === $to && CreateIndexInput::class === ($context['input']['class'] ?? null);
    }

    /**
     * @param CreateIndexInput $object  input object
     * @param string           $to      target class
     * @param array<mixed>     $context context
     *
     * @throws \Exception
     *
     * @return Index|null
     */
    public function transform($object, string $to, array $context = [])
    {
        $entityType = $object->entityType;
        $localizedCatalogCode = $object->localizedCatalog;

        $metadata = $this->metadataRepository->findOneBy(['entity' => $entityType]);
        if (!$metadata) {
            throw new InvalidArgumentException(sprintf('Entity type [%s] does not exist', $entityType));
        }
        if (null === $metadata->getEntity()) {
            throw new InvalidArgumentException(sprintf('Entity type [%s] is not defined', $entityType));
        }

        $catalog = $this->localizedCatalogRepository->findByCodeOrId($localizedCatalogCode);
        if (null === $catalog) {
            throw new InvalidArgumentException(sprintf('Localized catalog of ID or code [%s] does not exist', $localizedCatalogCode));
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
