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

namespace Gally\Fixture\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;

class ORMPsqlSequenceProvider
{
    public function __construct(
        private EntityManager $entityManager,
    ) {
    }

    public function sequence($name)
    {
        $sequenceGenerator = new SequenceGenerator($name, 1);

        return $sequenceGenerator->generateId($this->entityManager, null);
    }
}
