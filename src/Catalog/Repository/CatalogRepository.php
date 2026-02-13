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

namespace Gally\Catalog\Repository;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Catalog\Entity\Catalog;

/**
 * @method Catalog|null find($id, $lockMode = null, $lockVersion = null)
 * @method Catalog|null findOneBy(array $criteria, array $orderBy = null)
 * @method Catalog[]    findAll()
 * @method Catalog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CatalogRepository extends ServiceEntityRepository
{
    private array $cache;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Catalog::class);
    }

    public function findByCodeOrId(int|string $identifier): Catalog
    {
        if (!isset($this->cache[$identifier])) {
            if (is_numeric($identifier)) {
                $catalog = $this->find($identifier);
            } else {
                $catalog = $this->findOneBy(['code' => $identifier]);
            }
            if (null === $catalog) {
                throw new InvalidArgumentException(\sprintf('Missing catalog [%s]', $identifier));
            }
            $this->cache[$identifier] = $catalog;
        }

        return $this->cache[$identifier];
    }
}
