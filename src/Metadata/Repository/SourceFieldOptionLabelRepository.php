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
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldOption;
use Gally\Metadata\Entity\SourceFieldOptionLabel;

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

    public function getRawLabelDataByOptionCodes($sourceFieldIds, $optionCodes): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();

        return $this->createQueryBuilder('ol')
            ->select(
                [
                    'ol.id as id',
                    'ol.label as label',
                    'lc.id as localized_catalog_id',
                    'sf.id as source_field_id',
                    'sfo.id as source_field_option_id',
                    'sfo.code',
                ]
            )
            ->join(SourceFieldOption::class, 'sfo', Join::ON, $exprBuilder->eq('ol.sourceFieldOption', 'sfo'))
            ->join(SourceField::class, 'sf', Join::ON, $exprBuilder->eq('sfo.sourceField', 'sf'))
            ->join(LocalizedCatalog::class, 'lc', Join::ON, $exprBuilder->eq('ol.localizedCatalog', 'lc'))
            ->where('sf.id IN (:sourceFieldIds)')
            ->andWhere('sfo.code IN (:optionCodes)')
            ->setParameter('sourceFieldIds', $sourceFieldIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('optionCodes', $optionCodes, Connection::PARAM_STR_ARRAY)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function massInsertOrUpdate(array $labelData): void
    {
        $labelData = array_map(
            fn ($data) => \sprintf(
                '(%s, %d, %d, %s)',
                $data['id'],
                $data['localized_catalog_id'],
                $data['source_field_option_id'],
                $data['label']
            ),
            $labelData
        );
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                \sprintf(
                    'INSERT INTO source_field_option_label (id, localized_catalog_id, source_field_option_id, label) ' .
                    'VALUES %s ON CONFLICT (localized_catalog_id, source_field_option_id) ' .
                    'DO UPDATE SET label = excluded.label',
                    implode(',', $labelData)
                )
            );
    }

    public function massDelete(array $labelIds): void
    {
        $this->createQueryBuilder('l')
            ->delete(SourceFieldOptionLabel::class, 'l')
            ->where('l.id in (:ids)')
            ->setParameter('ids', $labelIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->execute();
    }
}
