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

namespace Gally\Configuration\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use Gally\Configuration\Controller\ConfigurationTreeController;
use Gally\Configuration\Resolver\ConfigurationTreeResolver;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(
            read: false,
            deserialize: false,
            uriTemplate: 'configuration_tree',
            controller: ConfigurationTreeController::class,
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
    ],
    graphQlOperations: [
        new Query(
            read: false,
            deserialize: false,
            resolver: ConfigurationTreeResolver::class,
            args: [],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
    ],
)]
class ConfigurationTree
{
    #[ApiProperty(identifier: true)]
    private string $code = 'configTree';
    private array $configTree;

    public function __construct(array $configTree)
    {
        $this->configTree = $configTree;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getConfigTree(): array
    {
        return $this->configTree;
    }
}
