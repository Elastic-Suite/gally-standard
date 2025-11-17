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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldOption;

/**
 * @method SourceFieldOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method SourceFieldOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method SourceFieldOption[]    findAll()
 * @method SourceFieldOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SourceFieldOptionRepository extends ServiceEntityRepository
{
    private array $entityFields = [
        'code' => 'code',
        'defaultLabel' => 'default_label',
        'position' => 'position',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceFieldOption::class);
    }

    public function getRawOptionDataByOptionCodes(array $sourceFieldIds, array $optionCodes): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();

        return $this->createQueryBuilder('sfo')
            ->select(
                array_merge(
                    ['sfo.id as id', 'sf.id as source_field_id'],
                    array_map(fn ($field) => "sfo.$field", array_keys($this->entityFields))
                )
            )
            ->join(SourceField::class, 'sf', Join::WITH, $exprBuilder->eq('sfo.sourceField', 'sf'))
            ->where('sf.id IN (:sourceFieldIds)')
            ->andWhere('sfo.code IN (:optionCodes)')
            ->setParameter('sourceFieldIds', $sourceFieldIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('optionCodes', $optionCodes, Connection::PARAM_STR_ARRAY)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function massInsertOrUpdate(array $optionsToUpdate): void
    {
        $optionsToUpdate = array_map(
            fn ($data) => \sprintf(
                '(%s, %d, %s, %s, %s)',
                $data['id'],
                $data['source_field_id'],
                ...array_values(array_map(fn ($field) => $data[$field], $this->entityFields))
            ),
            $optionsToUpdate
        );
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                \sprintf(
                    'INSERT INTO source_field_option (id, source_field_id, %s) ' .
                    'VALUES %s ON CONFLICT (source_field_id, code) ' .
                    'DO UPDATE SET %s',
                    implode(',', $this->entityFields),
                    implode(',', $optionsToUpdate),
                    implode(',', array_map(fn ($field) => "$field = excluded.$field", $this->entityFields))
                )
            );
    }
}
