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

namespace Gally\Index\DataTransformer;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Gally\Index\Dto\InstallIndexInput;
use Gally\Index\Model\Index;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Gally\Index\Service\IndexOperation;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class InstallIndexDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private IndexOperation $indexOperation,
        private IndexRepositoryInterface $indexRepository,
    ) {
    }

    /**
     * @param object|array<mixed> $data    object on normalize / array on denormalize
     * @param string              $to      target class
     * @param array<mixed>        $context context
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return
            Index::class === $to
            && OperationType::ITEM === ($context['operation_type'] ?? '')
            && InstallIndexInput::class === ($context['input']['class'] ?? '')
            && $context[AbstractNormalizer::OBJECT_TO_POPULATE] instanceof Index;
    }

    /**
     * @param InstallIndexInput $object  input object
     * @param string            $to      target class
     * @param array<mixed>      $context context
     *
     * @throws InvalidArgumentException
     *
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        $index = $context[AbstractNormalizer::OBJECT_TO_POPULATE];

        $this->indexOperation->installIndexByName($index->getName());

        // Reload the index to get updated aliases.
        return $this->indexRepository->findByName($index->getName());
    }
}
