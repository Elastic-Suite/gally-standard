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

namespace Gally\GraphQl\Decoration\Type;

use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\Metadata\GraphQl\Operation;
use Gally\Configuration\Repository\ConfigurationRepository;

/**
 * Allows to add dynamically rename graphql queries.
 */
class RenameGraphQlQuery implements FieldsBuilderEnumInterface
{
    public function __construct(
        private ConfigurationRepository $configurationRepository,
        private FieldsBuilderEnumInterface $decorated,
    ) {
    }

    public function getCollectionQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        $fields = $this->decorated->getCollectionQueryFields($resourceClass, $operation, $configuration);
        $graphqlQueryRenamings = $this->configurationRepository->getScopedConfigValue('gally.graphql_query_renaming');

        if (\array_key_exists($resourceClass, $graphqlQueryRenamings)) {
            foreach ($graphqlQueryRenamings[$resourceClass]['renamings'] as $oldName => $newName) {
                if (\array_key_exists($oldName, $fields)) {
                    $fields[$newName] = $fields[$oldName];
                    unset($fields[$oldName]);
                }
            }
        }

        return $fields;
    }

    public function getItemQueryFields(string $resourceClass, Operation $operation, array $configuration): array
    {
        $fields = $this->decorated->getItemQueryFields($resourceClass, $operation, $configuration);
        $graphqlQueryRenamings = $this->configurationRepository->getScopedConfigValue('gally.graphql_query_renaming');

        if (\array_key_exists($resourceClass, $graphqlQueryRenamings)) {
            foreach ($graphqlQueryRenamings[$resourceClass]['renamings'] as $oldName => $newName) {
                if (\array_key_exists($oldName, $fields)) {
                    $fields[$newName] = $fields[$oldName];
                    unset($fields[$oldName]);
                }
            }
        }

        return $fields;
    }

    public function getNodeQueryFields(): array
    {
        return $this->decorated->getNodeQueryFields();
    }

    public function getMutationFields(string $resourceClass, Operation $operation): array
    {
        return $this->decorated->getMutationFields($resourceClass, $operation);
    }

    public function getSubscriptionFields(string $resourceClass, Operation $operation): array
    {
        return $this->decorated->getSubscriptionFields($resourceClass, $operation);
    }

    public function getResourceObjectTypeFields(?string $resourceClass, Operation $operation, bool $input, int $depth = 0, ?array $ioMetadata = null): array
    {
        return $this->decorated->getResourceObjectTypeFields($resourceClass, $operation, $input, $depth, $ioMetadata);
    }

    public function resolveResourceArgs(array $args, Operation $operation): array
    {
        return $this->decorated->resolveResourceArgs($args, $operation);
    }

    public function getEnumFields(string $enumClass): array
    {
        return $this->decorated->getEnumFields($enumClass);
    }
}
