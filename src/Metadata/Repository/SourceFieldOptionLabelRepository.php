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

namespace Gally\Metadata\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Metadata\Model\SourceFieldOptionLabel;

/**
 * @method SourceFieldOptionLabel|null find($id, $lockMode = null, $lockVersion = null)
 * @method SourceFieldOptionLabel|null findOneBy(array $criteria, array $orderBy = null)
 * @method SourceFieldOptionLabel[]    findAll()
 * @method SourceFieldOptionLabel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SourceFieldOptionLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceFieldOptionLabel::class);
    }
}
