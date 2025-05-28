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

namespace Gally\Configuration\Service;

use Gally\Configuration\Entity\ConfigurationTree;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationTreeBuilder
{
    public function __construct(
        private array $configurationTree,
        private TranslatorInterface $translator,
    ) {
    }

    public function build(): ConfigurationTree
    {
        // Translate scope label
        foreach ($this->configurationTree['scopes'] as $code => &$scope) {
            $scope['label'] = isset($scope['labelKey'])
                ? $this->translator->trans($scope['labelKey'], [], 'gally_configuration')
                : $code;
            unset($scope['labelKey']);
        }

        // Translate group label
        foreach ($this->configurationTree['groups'] as $groupCode => &$group) {
            $group['label'] = isset($group['labelKey'])
                ? $this->translator->trans($group['labelKey'], [], 'gally_configuration')
                : $groupCode;
            unset($group['labelKey']);

            // Translate fieldset label
            foreach ($group['fieldsets'] as $fieldsetCode => &$fieldset) {
                $fieldset['label'] = isset($fieldset['labelKey'])
                    ? $this->translator->trans($fieldset['labelKey'], [], 'gally_configuration')
                    : $fieldsetCode;
                unset($fieldset['labelKey']);

                if (isset($fieldset['tooltipKey'])) {
                    $fieldset['tooltip'] = $this->translator->trans($fieldset['tooltipKey'], [], 'gally_configuration');
                    unset($fieldset['tooltipKey']);
                }

                // Translate field label
                foreach ($fieldset['fields'] as $fieldCode => &$field) {
                    $field['label'] = isset($field['labelKey'])
                        ? $this->translator->trans($field['labelKey'], [], 'gally_configuration')
                        : $fieldCode;
                    unset($field['labelKey']);

                    if (isset($field['placeholderKey'])) {
                        $field['placeholder'] = $this->translator->trans($field['placeholderKey'], [], 'gally_configuration');
                        unset($field['placeholderKey']);
                    }

                    if (isset($field['infoTooltipKey'])) {
                        $field['infoTooltip'] = $this->translator->trans($field['infoTooltipKey'], [], 'gally_configuration');
                        unset($field['infoTooltipKey']);
                    }
                }
            }
        }

        return new ConfigurationTree($this->configurationTree);
    }
}
