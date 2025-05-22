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

namespace Gally\Search\State\Facet;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Category\Entity\Category;
use Gally\Exception\LogicException;
use Gally\Metadata\Entity\SourceField;
use Gally\ResourceMetadata\Service\ResourceMetadataManager;
use Gally\Search\Entity\Facet;
use Gally\Search\Repository\Facet\ConfigurationRepository;

final class ConfigurationItemProvider implements ProviderInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ResourceMetadataManager $resourceMetadataManager,
        private ProviderInterface $itemProviderNoEagerLoading,
        private ProviderInterface $itemProvider,
    ) {
    }

    /**
     * @return Facet\Configuration|object|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (preg_match('/^([^-]+)-(.*)$/', $uriVariables['id'], $matches)) {
            [$fullMatch, $sourceFieldId, $categoryId] = $matches;
        } else {
            throw new LogicException("Invalid facet configuration ID format : {$uriVariables['id']}.");
        }

        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($operation->getClass());
        /** @var ConfigurationRepository $repository */
        $repository = $manager->getRepository($operation->getClass());

        // Force loading sub-entity in order to avoid having proxies.
        /** @var ?SourceField $sourceField */
        $sourceField = $this->itemProvider->provide($this->resourceMetadataManager->getOperation(SourceField::class), ['id' => $sourceFieldId]);
        if (null === $sourceField) {
            throw new LogicException("The source field with the id '{$sourceFieldId}' does not exist.");
        }

        $category = $this->itemProvider->provide($this->resourceMetadataManager->getOperation(Category::class), ['id' => $categoryId]);
        if ('0' !== $categoryId && null === $category) {
            throw new LogicException("The category with the id '{$categoryId}' does not exist.");
        }

        $repository->setMetadata($sourceField->getMetadata());
        $defaultFacetConfiguration = null;

        if ($categoryId) {
            $repository->setCategoryId(null);
            /** @var ?Facet\Configuration $defaultFacetConfiguration */
            $defaultFacetConfiguration = $this->itemProviderNoEagerLoading->provide(
                $this->resourceMetadataManager->getOperation(Facet\Configuration::class),
                ['id' => implode('-', [$sourceFieldId, 0])],
                $context,
            );
        }

        if (!$defaultFacetConfiguration) {
            $defaultFacetConfiguration = new Facet\Configuration($sourceField, null);
        } else {
            $defaultFacetConfiguration->setId(implode('-', [$sourceFieldId, 0]));
        }

        $repository->setCategoryId($categoryId);

        /** @var ?Facet\Configuration $facetConfiguration */
        $facetConfiguration = $this->itemProviderNoEagerLoading->provide(
            $this->resourceMetadataManager->getOperation(Facet\Configuration::class),
            $uriVariables,
            $context,
        );
        $facetConfiguration = $facetConfiguration ?: new Facet\Configuration($sourceField, $category);
        $facetConfiguration->setId($uriVariables['id']);

        $facetConfiguration->initDefaultValue($defaultFacetConfiguration);

        return $facetConfiguration;
    }
}
