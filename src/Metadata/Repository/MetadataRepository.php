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

namespace Gally\Metadata\Repository;

use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Metadata\Entity\Metadata;
use Gally\ResourceMetadata\Service\ResourceMetadataManager;

/**
 * @method Metadata|null find($id, $lockMode = null, $lockVersion = null)
 * @method Metadata[]    findAll()
 * @method Metadata[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetadataRepository extends ServiceEntityRepository
{
    private array $cache;

    public function __construct(
        ManagerRegistry $registry,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private ResourceMetadataManager $resourceMetadataManager,
    ) {
        parent::__construct($registry, Metadata::class);
    }

    public function findByEntity(string $entityType, $withCache = true): ?Metadata
    {
        if (!$withCache || !isset($this->cache[$entityType])) {
            $metadata = $this->findOneBy(['entity' => $entityType]);
            if (!$metadata) {
                throw new InvalidArgumentException(\sprintf('Entity type [%s] does not exist', $entityType));
            }
            if (null === $metadata->getEntity()) {
                throw new InvalidArgumentException(\sprintf('Entity type [%s] is not defined', $entityType));
            }
            $this->cache[$entityType] = $metadata;
        }

        return $this->cache[$entityType];
    }

    public function findByRessourceClass(string $resourceClass): ?Metadata
    {
        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        $entityType = $this->resourceMetadataManager->getMetadataEntity($resourceMetadata);
        if (null === $entityType) {
            throw new ResourceClassNotFoundException(\sprintf('Resource "%s" has no declared metadata entity.', $resourceClass));
        }

        return $this->findByEntity($entityType);
    }

    public function getAllIds(): array
    {
        return $this->createQueryBuilder('o', 'o.id')->select('o.id')->getQuery()->getResult();
    }
}
