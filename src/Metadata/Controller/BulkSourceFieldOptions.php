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

use Gally\Metadata\State\SourceFieldOptionProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class BulkSourceFieldOptions extends AbstractController
{
    public function __construct(
        private SourceFieldOptionProcessor $sourceFieldOptionProcessor,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $options = json_decode($request->getContent(), true);

        return $this->sourceFieldOptionProcessor->persistMultiple($options);
    }
}
