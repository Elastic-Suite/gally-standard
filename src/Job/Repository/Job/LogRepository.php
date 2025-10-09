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

namespace Gally\Job\Repository\Job;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Job\Entity\Job;

/**
 * @method Job\Log|null find($id, $lockMode = null, $lockVersion = null)
 * @method Job\Log|null findOneBy(array $criteria, array $orderBy = null)
 * @method Job\Log[]    findAll()
 * @method Job\Log[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job\Log::class);
    }

    /**
     * Check if a job has a specific log message.
     */
    public function hasMessage(Job $job, string $message): bool
    {
        return null !== $this->findOneBy(['job' => $job, 'message' => $message]);
    }
}
