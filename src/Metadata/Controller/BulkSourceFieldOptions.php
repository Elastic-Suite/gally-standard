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
use Gally\Metadata\State\SourceFieldOptionProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class BulkSourceFieldOptions extends AbstractController
{
    public function __construct(
        private SourceFieldOptionProcessor $sourceFieldOptionProcessor,
        private IriConverterInterface $iriConverter,
        private ?PurgerInterface $purger,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $sourceFieldOptions = json_decode($request->getContent(), true);

        $sourceFieldOptionEntities = $this->sourceFieldOptionProcessor->persistMultiple($sourceFieldOptions);
        $tags = [];

        foreach ($sourceFieldOptionEntities as $sourceFieldOption) {
            $iri = $this->iriConverter->getIriFromResource($sourceFieldOption);
            $tags[$iri] = $iri;
        }

        $this->purger?->purge(array_values($tags));

        return $sourceFieldOptionEntities;
    }
}
