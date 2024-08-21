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

namespace Gally\Search\State\Facet;

use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gally\Category\Model\Category;
use Gally\Exception\LogicException;
use Gally\Metadata\Model\SourceField;
use Gally\ResourceMetadata\Service\ResourceMetadataManager;
use Gally\Search\Model\Facet;
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
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     *
     * @return PartialPaginatorInterface|array|Facet\Configuration|object|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        [$sourceFieldId, $categoryId] = explode('-', $uriVariables['id']);
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

        // todo upgrade @Pigau see if the conditions on operation types are still necessary ?
        if ($categoryId
            && (
                ($operation instanceof Get)
                || ($operation instanceof Patch)
                || ($operation instanceof Put)
                || ($operation instanceof Query)
                || ($operation instanceof Mutation)
            )
        ) {
            $repository->setCategoryId(null);
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