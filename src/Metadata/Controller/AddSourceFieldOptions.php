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

namespace Gally\Metadata\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Model\SourceFieldOption;
use Gally\Metadata\Repository\SourceFieldRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Serializer\Serializer;

#[AsController]
class AddSourceFieldOptions extends AbstractController
{
    public function __construct(
        private SourceFieldRepository $sourceFieldRepository,
        private Serializer $serializer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(int $id, Request $request): SourceField
    {
        $sourceField = $this->sourceFieldRepository->find($id);
        $options = json_decode($request->getContent(), true);

        if (!$sourceField) {
            throw new InvalidArgumentException('The source field doesn\'t exist.');
        }

        if (SourceField\Type::TYPE_SELECT != $sourceField->getType()) {
            throw new InvalidArgumentException('You can only add options to a source field of type "select".');
        }

        foreach ($options as $optionData) {
            /** @var SourceFieldOption $option */
            $option = $this->serializer->denormalize($optionData, SourceFieldOption::class, 'jsonld', []);
            if (!$option->getCode()) {
                throw new InvalidArgumentException('A code value is required for source field option.');
            }
            if (!$option->getDefaultLabel()) {
                throw new InvalidArgumentException("The option {$option->getCode()} doesn't have a default label.");
            }
            if ($option->getId() && $option->getSourceField()->getId() !== $sourceField->getId()) {
                throw new InvalidArgumentException(sprintf('The option %s is not linked to the source field %s.', $option->getId(), $sourceField->getId()));
            }

            $sourceField->addOption($option);
            $this->entityManager->persist($option);
        }

        $this->entityManager->persist($sourceField);
        $this->entityManager->flush();

        return $sourceField;
    }
}
