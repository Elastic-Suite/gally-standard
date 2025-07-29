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

class EntityProvider
{
    public function __construct(
        private EntityManager $entityManager
    ) {
    }

    public function findByCode(string $classname, string $field, string $code): object
    {
        $repository = $this->entityManager->getRepository($classname);

        return $repository->findOneBy([$field => $code]);
    }
}
