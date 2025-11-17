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
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceFieldLabel;

/**
 * @method SourceFieldLabel|null find($id, $lockMode = null, $lockVersion = null)
 * @method SourceFieldLabel|null findOneBy(array $criteria, array $orderBy = null)
 * @method SourceFieldLabel[]    findAll()
 * @method SourceFieldLabel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SourceFieldLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SourceFieldLabel::class);
    }

    public function getRawLabelDataBySourceFieldCodes($metadataIds, $sourceFieldCodes): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();

        return $this->createQueryBuilder('l')
            ->select(
                [
                    'l.id as id',
                    'l.label as label',
                    'lc.id as localized_catalog_id',
                    'm.id as metadata_id',
                    'sf.id as source_field_id',
                    'sf.code',
                ]
            )
            ->join(SourceField::class, 'sf', Join::WITH, $exprBuilder->eq('l.sourceField', 'sf'))
            ->join(Metadata::class, 'm', Join::WITH, $exprBuilder->eq('sf.metadata', 'm'))
            ->join(LocalizedCatalog::class, 'lc', Join::WITH, $exprBuilder->eq('l.localizedCatalog', 'lc'))
            ->where('m.id IN (:metadataIds)')
            ->andWhere('sf.code IN (:sourceFieldCodes)')
            ->setParameter('metadataIds', $metadataIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('sourceFieldCodes', $sourceFieldCodes, Connection::PARAM_STR_ARRAY)
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
                $data['source_field_id'],
                $data['label']
            ),
            $labelData
        );
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                \sprintf(
                    'INSERT INTO source_field_label (id, localized_catalog_id, source_field_id, label) ' .
                    'VALUES %s ON CONFLICT (localized_catalog_id, source_field_id) ' .
                    'DO UPDATE SET label = excluded.label',
                    implode(',', $labelData)
                )
            );
    }

    public function massDelete(array $labelIds): void
    {
        $this->createQueryBuilder('l')
            ->delete(SourceFieldLabel::class, 'l')
            ->where('l.id in (:ids)')
            ->setParameter('ids', $labelIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->execute();
    }
}
