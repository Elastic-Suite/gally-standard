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

namespace Gally\Metadata\EventSubscriber;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Gally\Exception\LogicException;
use Gally\Metadata\Model\SourceField;
use Gally\Metadata\Repository\SourceFieldRepository;

class ValidateSourceFieldCode
{
    public function __construct(
        private SourceFieldRepository $sourceFieldRepository,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->validateSourceFieldCode($args->getObject(), 'create');
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->validateSourceFieldCode($args->getObject(), 'update');
    }

    private function validateSourceFieldCode(object $entity, string $action): void
    {
        if (!$entity instanceof SourceField) {
            return;
        }
        $sourceField = $entity;
        $metadata = $sourceField->getMetadata();

        /*
         * Allows to avoid code conflicts between scalar and structured source fields.
         * For example with these validations we can't create a source field 'category' and 'category.id' and vice versa.
         */
        if ($sourceField->isNested()) {
            $sourceFields = $this->sourceFieldRepository->findBy([
                'code' => $sourceField->getNestedPath(),
                'metadata' => $metadata,
            ]);

            if (\count($sourceFields) > 0) {
                throw new LogicException("You can't $action a source field with the code '{$sourceField->getCode()}' because a source field with the code '{$sourceField->getNestedPath()}' exists.");
            }
        } else {
            $sourceFields = $this->sourceFieldRepository->findByCodePrefix($sourceField->getCode() . '.', $metadata);

            if (\count($sourceFields) > 0) {
                throw new LogicException("You can't $action a source field with the code '{$sourceField->getCode()}' because a source field with the code '{$sourceField->getCode()}.*' exists.");
            }
        }
    }
}
