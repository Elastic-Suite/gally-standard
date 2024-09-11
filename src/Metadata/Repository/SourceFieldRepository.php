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
use Gally\Metadata\Entity\Metadata;
use Gally\Metadata\Entity\SourceField;
use Gally\Metadata\Entity\SourceField\Type;

/**
 * @method SourceField|null find($id, $lockMode = null, $lockVersion = null)
 * @method SourceField|null findOneBy(array $criteria, array $orderBy = null)
 * @method SourceField[]    findAll()
 * @method SourceField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SourceFieldRepository extends ServiceEntityRepository
{
    private array $entityFields = [
        'code' => 'code',
        'defaultLabel' => 'default_label',
        'type' => 'type',
        'weight' => 'weight',
        'isSearchable' => 'is_searchable',
        'isFilterable' => 'is_filterable',
        'isSortable' => 'is_sortable',
        'isSpellchecked' => 'is_spellchecked',
        'isUsedForRules' => 'is_used_for_rules',
        'isUsedInAutocomplete' => 'is_used_in_autocomplete',
        'isSystem' => 'is_system',
        'search' => 'search',
    ];

    public function __construct(ManagerRegistry $registry, private MetadataRepository $metadataRepository)
    {
        parent::__construct($registry, SourceField::class);
    }

    /**
     * Get list of all sourceField properties managed in bulk api
     * (This is used to detect missing property in bulk management).
     */
    public function getManagedSourceFieldProperty(): array
    {
        return array_flip($this->entityFields);
    }

    /**
     * @return SourceField[]
     */
    public function getSortableFields(string $entityCode, array $attributeToExclude = []): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->where('o.metadata = :metadata')
            ->andWhere('o.isSortable = true')
            ->setParameter('metadata', $this->metadataRepository->findOneBy(['entity' => $entityCode]));

        if (!empty($attributeToExclude)) {
            $queryBuilder
                ->andWhere('o.code not in (:excluded_attribute)')
                ->setParameter('excluded_attribute', $attributeToExclude);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableInRequestFields(string $entityCode): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();

        $query = $this->createQueryBuilder('o')
            ->where('o.metadata = :metadata')
            ->andWhere(
                $exprBuilder->orX(
                    $exprBuilder->eq('o.isFilterable', 'true'),
                    $exprBuilder->eq('o.isUsedForRules', 'true'),
                )
            )
            ->setParameter('metadata', $this->metadataRepository->findOneBy(['entity' => $entityCode]))
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return SourceField[]
     */
    public function findByCodePrefix(string $codePrefix, Metadata $metadata): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();
        $query = $this->createQueryBuilder('s')
            ->where('s.metadata = :metadata')
            ->andWhere($exprBuilder->like('s.code', $exprBuilder->concat("'$codePrefix'", "'%'")))
            ->setParameter('metadata', $metadata)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableInAggregationFields(string $entityCode): array
    {
        return $this->findBy(
            [
                'metadata' => $this->metadataRepository->findBy(['entity' => $entityCode]),
                'isFilterable' => true,
            ]
        );
    }

    /**
     * @return SourceField[]
     */
    public function getComplexeFields(Metadata $metadata): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();
        $query = $this->createQueryBuilder('s')
            ->where('s.metadata = :metadata')
            ->andWhere(
                $exprBuilder->orX(
                    $exprBuilder->like('s.code', "'%.%'"),
                    $exprBuilder->in(
                        's.type',
                        [Type::TYPE_SELECT, Type::TYPE_PRICE, Type::TYPE_STOCK, Type::TYPE_CATEGORY]
                    )
                )
            )
            ->setParameter('metadata', $metadata)
            ->getQuery();

        return $query->getResult();
    }

    public function getRawSourceFieldTypeByIds(array $sourceFieldIds): array
    {
        return $this->createQueryBuilder('sf', 'sf.id')
            ->select(['sf.id', 'sf.type'])
            ->where('sf.id IN (:sourceFieldIds)')
            ->setParameter('sourceFieldIds', $sourceFieldIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function getRawSourceFieldDataByCodes(array $metadataIds, array $sourceFieldCodes): array
    {
        $exprBuilder = $this->getEntityManager()->getExpressionBuilder();

        return $this->createQueryBuilder('sf')
            ->select(
                array_merge(
                    ['sf.id', 'm.id as metadata'],
                    array_map(fn ($field) => "sf.$field", array_keys($this->entityFields))
                )
            )
            ->join(Metadata::class, 'm', Join::WITH, $exprBuilder->eq('sf.metadata', 'm'))
            ->where('m.id IN (:metadataIds)')
            ->andWhere('sf.code IN (:sourceFieldCodes)')
            ->setParameter('metadataIds', $metadataIds, Connection::PARAM_INT_ARRAY)
            ->setParameter('sourceFieldCodes', $sourceFieldCodes, Connection::PARAM_STR_ARRAY)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function massInsertOrUpdate(array $sourceFieldData): void
    {
        $sourceFieldData = array_map(
            fn ($data) => \sprintf(
                '(%s, %s' . str_repeat(',%s', \count($this->entityFields)) . ')',
                $data['id'],
                $data['metadata_id'],
                ...array_values(array_map(fn ($field) => $data[$field], $this->entityFields))
            ),
            $sourceFieldData
        );

        $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                \sprintf(
                    'INSERT INTO source_field (id, metadata_id, %s) ' .
                    'VALUES %s ON CONFLICT (metadata_id, code) ' .
                    'DO UPDATE SET %s',
                    implode(',', $this->entityFields),
                    implode(',', $sourceFieldData),
                    implode(',', array_map(fn ($field) => "$field = excluded.$field", $this->entityFields))
                )
            );
    }
}
