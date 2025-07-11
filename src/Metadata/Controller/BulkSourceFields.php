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

namespace Gally\Metadata\Controller;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use Gally\Metadata\State\SourceFieldProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class BulkSourceFields extends AbstractController
{
    public function __construct(
        private SourceFieldProcessor $sourceFieldProcessor,
        private IriConverterInterface $iriConverter,
        private PurgerInterface $purger,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $sourceFields = json_decode($request->getContent(), true);

        $sourceFieldEntities = $this->sourceFieldProcessor->persistMultiple($sourceFields);
        $tags = [];

        foreach ($sourceFieldEntities as $sourceField) {
            $iri = $this->iriConverter->getIriFromResource($sourceField);
            $tags[$iri] = $iri;
        }

        $this->purger->purge(array_values($tags));

        return $sourceFieldEntities;
    }
}
