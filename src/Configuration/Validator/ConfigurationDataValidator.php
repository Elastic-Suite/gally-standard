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
use Gally\Configuration\Repository\ConfigurationRepository;
use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;

class ConfigurationDataValidator
{
    private array $existingLocalizedCatalogCodes;

    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private ContainerConfigurationProvider $configurationProvider,
        private ConfigurationRepository $configurationRepository,
    ) {
    }

    /**
     * Validate if the given object is a valid sourceField that can be persisted.
     *
     * @return void
     */
    public function validateObject(Configuration $conf)
    {
        if (null === $conf->getPath()) {
            throw new InvalidArgumentException('Path is required for configuration.');
        }
        if (!$this->configurationRepository->isPathValid($conf->getPath())) {
            throw new InvalidArgumentException('The given configuration path is blacklisted and must not be used with the configuration manager.');
        }
        if (null === $conf->getScopeType()) {
            throw new InvalidArgumentException('Scope type is required for configuration.');
        }
        if (!\in_array($conf->getScopeType(), ConfigurationRepository::getAvailableScopeTypes(), true)) {
            throw new InvalidArgumentException(\sprintf('Invalid scope type : "%s".', $conf->getScopeType()));
        }

        $isValid = true;
        switch ($conf->getScopeType()) {
            case Configuration::SCOPE_GENERAL:
                $isValid = null === $conf->getScopeCode();
                break;
            case Configuration::SCOPE_LANGUAGE:
                $validLanguages = array_unique(
                    array_map(fn ($locale) => \Locale::getPrimaryLanguage($locale), \ResourceBundle::getLocales(''))
                );
                $isValid = \in_array($conf->getScopeCode(), $validLanguages, true);
                break;
            case Configuration::SCOPE_LOCALE:
                $isValid = \in_array($conf->getScopeCode(), \ResourceBundle::getLocales(''), true);
                break;
            case Configuration::SCOPE_REQUEST_TYPE:
                $isValid = \in_array($conf->getScopeCode(), $this->configurationProvider->getAvailableRequestType('product'), true);
                break;
            case Configuration::SCOPE_LOCALIZED_CATALOG:
                $isValid = \in_array($conf->getScopeCode(), $this->getExistingLocalizedCatalogCode(), true);
                break;
        }

        if (!$isValid) {
            throw new InvalidArgumentException($conf->getScopeCode() ? \sprintf('Invalid scope code "%s" for scope "%s".', $conf->getScopeCode(), $conf->getScopeType()) : \sprintf('Invalid scope code null for scope "%s".', $conf->getScopeType()));
        }
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
