<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Locale\DataProvider\Source;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\Locale\Model\Source\LocaleGroupOption;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleGroupOptionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return LocaleGroupOption::class === $resourceClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): array
    {
        $usedLocales = array_column($this->localizedCatalogRepository->findUsedLocales(), 'locale');

        return [
            $this->getGroupOption(
                'used_locale',
                'gally.locale.used.label',
                $usedLocales
            ),
            $this->getGroupOption(
                'unused_locale',
                'gally.locale.unused.label',
                Locales::getLocales(),
                $usedLocales
            ),
        ];
    }

    protected function getGroupOption(string $value, string $label, array $locales, array $excludedLocales = []): array
    {
        $groupOption['value'] = $groupOption['id'] = $value;
        $groupOption['label'] = $this->translator->trans($label, [], 'gally_locale');

        $options = [];
        foreach ($locales as $locale) {
            // The Regexp allows to verify if the locale has the format xx_XX, locale with the format xx are skipped.
            if (\in_array($locale, $excludedLocales, true) || 1 !== preg_match('/[a-z]{2}_[A-Z]{2}$/', $locale)) {
                continue;
            }

            $option['value'] = $locale;
            try {
                $option['label'] = ucfirst(Locales::getName($locale));
            } catch (MissingResourceException $e) {
                $option['label'] = $locale;
            }

            $options[] = $option;
        }
        $groupOption['options'] = $options;

        return $groupOption;
    }
}
