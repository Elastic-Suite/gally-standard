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

namespace Gally\Configuration\Controller;

use Gally\Configuration\Service\ConfigurationTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ConfigurationTreeController extends AbstractController
{
    public function __construct(private ConfigurationTreeBuilder $builder)
    {
    }

    public function __invoke()
    {
        return $this->builder->build();
    }
}
