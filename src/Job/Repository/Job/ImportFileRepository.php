<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Licensed to Smile-SA. All rights reserved. No warranty, explicit or implicit, provided.
 *            Unauthorized copying of this file, via any medium, is strictly prohibited.
 */

declare(strict_types=1);

namespace Gally\Job\Repository\Job;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Job\Entity\Job;

/**
 * @method Job\ImportFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method Job\ImportFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method Job\ImportFile[]    findAll()
 * @method Job\ImportFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job\ImportFile::class);
    }
}
