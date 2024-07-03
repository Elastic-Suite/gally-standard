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

namespace Gally\GraphQl\Decoration\Type;

use ApiPlatform\GraphQl\Type\FieldsBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Metadata\GraphQl\Operation;

/**
 * Allows to add dynamically rename graphql queries.
 */
class RenameGraphQlQuery implements FieldsBuilderInterface
{
    public function __construct(
        private array $graphqlQueryRenamings,
        private FieldsBuilderInterface $decorated,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionQueryFields(string $resourceClass, Operation $operation, array $configuration): array {
        $fields = $this->decorated->getCollectionQueryFields($resourceClass, $operation, $configuration);

        if (\array_key_exists($resourceClass, $this->graphqlQueryRenamings)) {
            foreach ($this->graphqlQueryRenamings[$resourceClass]['renamings'] as $oldName => $newName) {
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

        if (\array_key_exists($resourceClass, $this->graphqlQueryRenamings)) {
            foreach ($this->graphqlQueryRenamings[$resourceClass]['renamings'] as $oldName => $newName) {
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

    public function getResourceObjectTypeFields(?string $resourceClass, Operation $operation, bool $input, int $depth = 0, array $ioMetadata = null): array
    {
        return $this->decorated->getResourceObjectTypeFields($resourceClass, $operation, $input, $depth, $ioMetadata);
    }

    public function resolveResourceArgs(array $args, Operation $operation): array
    {
        return $this->decorated->resolveResourceArgs($args, $operation);
    }
}
