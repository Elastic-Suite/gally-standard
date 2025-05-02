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

namespace Gally\Configuration\Validator;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Configuration\Entity\Configuration;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;

class ConfigurationDataValidator
{
    private array $existingLocalizedCatalogCodes;

    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ContainerConfigurationProvider $configurationProvider,
    ) {
    }

    /**
     * Validate if the given object is a valid sourceField that can be persisted.
     *
     * @return void
     */
    public function validateObject(Configuration $configuration)
    {
        if (null === $configuration->getPath()) {
            throw new InvalidArgumentException('Path is required for configuration.');
        }
        if (null === $configuration->getScopeType()) {
            throw new InvalidArgumentException('Scope type is required for configuration.');
        }
        if (!\in_array($configuration->getScopeType(), $configuration->getAvailableScopeTypes(), true)) {
            throw new InvalidArgumentException(\sprintf('Invalid scope type : "%s".', $configuration->getScopeType()));
        }
        if (null === $configuration->getScopeCode()) {
            return;
        }

        $isValid = true;
        switch ($configuration->getScopeType()) {
            case Configuration::SCOPE_GENERAL:
                $isValid = false;
                break;
            case Configuration::SCOPE_LOCALE:
                $canonical = \Locale::canonicalize($configuration->getScopeCode());
                $isValid = \in_array($canonical, \ResourceBundle::getLocales(''), true);
                break;
            case Configuration::SCOPE_REQUEST_TYPE:
                $isValid = \in_array($configuration->getScopeCode(), $this->configurationProvider->getAvailableRequestType('product'), true);
                break;
            case Configuration::SCOPE_LOCALIZED_CATALOG:
                $isValid = \in_array($configuration->getScopeCode(), $this->getExistingLocalizedCatalogCode(), true);
                break;
        }

        if (!$isValid) {
            throw new InvalidArgumentException(\sprintf('Invalid scope code "%s" for scope "%s".', $configuration->getScopeCode(), $configuration->getScopeType()));
        }
    }

    /**
     * Validate if the given data are valid to be insert in db as a source field.
     *
     * @param array $rawData              source field data
     * @param array $existingSourceFields A multidimensional array with:
     *                                    - metadata id as first level keys
     *                                    - source field code as second level keys
     *                                    - data currently in the data as values
     */
    public function validateRawData(array $rawData, array $existingSourceFields): void
    {
        //        foreach ($this->requiredFields as $requiredField) {
        //            if (!\array_key_exists($requiredField, $rawData)) {
        //                throw new InvalidArgumentException("A $requiredField value is required for source field.");
        //            }
        //        }
        //
        //        $metadataId = (int) str_replace($this->routePrefix . '/metadata/', '', $rawData['metadata']);
        //        $rawData['metadata'] = $metadataId;
        //
        //        if (!\array_key_exists($metadataId, $this->getExistingMetadataIds())) {
        //            throw new InvalidArgumentException("Item not found for \"{$rawData['metadata']}\".");
        //        }
        //
        //        // Prevent user to update a system source field, only the value of $updatableProperties can be changed.
        //        if (isset($existingSourceFields[$metadataId][$rawData['code']])) {
        //            $existing = $existingSourceFields[$metadataId][$rawData['code']];
        //            if ($existing['isSystem']) {
        //                foreach ($rawData as $field => $value) {
        //                    if (
        //                        'labels' !== $field // Don't check sub-entities.
        //                        && !\in_array($field, $this->updatableProperties, true)
        //                        && $value !== ($existingSourceFields[$metadataId][$rawData['code']][$field] ?? null)
        //                    ) {
        //                        throw new InvalidArgumentException(\sprintf("The source field '%s' cannot be updated because it is a system source field, only the value of '%s' can be changed.", $rawData['code'], implode("', '", $this->updatableProperties)));
        //                    }
        //                }
        //            }
        //        }
        //
        //        // validate labels data
        //        foreach ($rawData['labels'] ?? [] as $label) {
        //            $localizedCatalogId = (int) str_replace($this->routePrefix . '/localized_catalogs/', '', $label['localizedCatalog']);
        //
        //            if (!\array_key_exists($localizedCatalogId, $this->getExistingLocalizedCatalog())) {
        //                throw new InvalidArgumentException("Item not found for \"{$label['localizedCatalog']}\".");
        //            }
        //        }
    }

    private function getExistingLocalizedCatalogCode(): array
    {
        if (!isset($this->existingLocalizedCatalogCodes)) {
            $this->existingLocalizedCatalogCodes = [];
            foreach ($this->localizedCatalogRepository->findAll() as $localizedCatalog) {
                $this->existingLocalizedCatalogCodes[] = $localizedCatalog->getCode();
            }
        }

        return $this->existingLocalizedCatalogCodes;
    }
}
