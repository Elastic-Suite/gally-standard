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

namespace Gally\Locale\State\Source;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageOptionProvider implements ProviderInterface
{
    public function __construct(
        private LocalizedCatalogRepository $localizedCatalogRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?array
    {
        $usedLocales = array_column($this->localizedCatalogRepository->findUsedLocales(), 'locale');

        return [
            $this->getGroupOption(
                'used_language',
                'gally.language.used.label',
                $usedLocales
            ),
            $this->getGroupOption(
                'unused_language',
                'gally.language.unused.label',
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

            $language = \Locale::getPrimaryLanguage($locale);

            $option['value'] = $language;
            $option['label'] = $language;

            $options[$language] = $option;
        }
        $groupOption['options'] = array_values($options);

        return $groupOption;
    }
}
