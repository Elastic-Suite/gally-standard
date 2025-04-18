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

namespace Gally\Category\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Category\Entity\Category;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllIndexedById(): array
    {
        return $this->createQueryBuilder('o', 'o.id')->getQuery()->getResult();
    }

    /**
     * @return Category\Configuration[]
     */
    public function getUnusedCategory(): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();

        $unusedCategory = $this->createQueryBuilder('category')
            ->where(
                $exprBuilder->notIn(
                    'category.id',
                    $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select('category_with_config')
                        ->from($this->getClassName(), 'category_with_config')
                        ->join(
                            Category\Configuration::class,
                            'localized_catalog_conf',
                            Join::WITH,
                            $exprBuilder->eq('category_with_config', 'localized_catalog_conf.category')
                        )
                        ->distinct()
                        ->getDQL()
                )
            )
            ->distinct();

        return $unusedCategory->getQuery()->getResult();
    }
}
