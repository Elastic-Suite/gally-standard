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

namespace Gally\Search\Hydrator;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\UnitOfWork;
use Gally\Search\Model\Facet\Configuration;

final class FacetConfigurationHydrator extends ObjectHydrator
{
    public const CODE = 'FacetConfigurationHydrator';

    /** @var mixed[] */
    protected array $idTemplate = [];

    protected function prepare(): void
    {
        parent::prepare();
        foreach ($this->resultSetMapping()->aliasMap as $dqlAlias => $className) {
            $this->idTemplate[$dqlAlias] = '';
        }

        // Force fetching the sub-entity to avoid proxy error in graphql request
        unset($this->_hints[UnitOfWork::HINT_DEFEREAGERLOAD]);
        $this->_hints['fetchMode'] = [
            Configuration::class => [
                'sourceField' => ClassMetadataInfo::FETCH_EAGER,
                'category' => ClassMetadataInfo::FETCH_EAGER,
                'metadata' => ClassMetadataInfo::FETCH_EAGER,
            ],
        ];
    }

    protected function hydrateAllData(): array
    {
        $result = [];

        while ($row = $this->statement()->fetchAssociative()) {
            $id = $this->idTemplate;
            $nonemptyComponents = [];
            $rowData = $this->gatherRowData($row, $id, $nonemptyComponents);
            $configurationFields = array_keys($rowData['data']['o']);

            if ($rowData['data']['default']['id'] == $rowData['data']['o']['id']) {
                $rowData['data']['default'] = [];
            }

            $defaultValues = array_merge(
                array_fill_keys($configurationFields, null),
                $this->filterData($rowData['data']['default'] ?? [])    // Source field default values
            );

            $defaultValues['id'] = $defaultValues['id'] ?? 'hydrator-default-id';

            /** @var Configuration $default */
            $default = $this->_uow->createEntity(Configuration::class, $defaultValues, $this->_hints);

            // Remove default configuration id.
            unset($defaultValues['id']);

            $data = array_merge(
                $defaultValues,                                     // Source field default values
                $this->filterData($rowData['data']['o']),           // Current category value
                $this->filterData($rowData['scalars'])              // Association ids
            );

            /** @var Configuration $obj */
            $obj = $this->_uow->createEntity(Configuration::class, $data, $this->_hints);

            $obj->initDefaultValue($default);

            $result[$obj->getSourceField()->getId()] = $obj;
        }

        return $result;
    }

    private function filterData(array $data): array
    {
        return array_filter($data, function ($item) {
            return null !== $item;
        });
    }
}
