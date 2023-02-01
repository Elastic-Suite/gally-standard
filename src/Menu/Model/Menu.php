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

namespace Gally\Menu\Model;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\Menu\Controller\MenuController;
use Gally\Menu\Resolver\MenuResolver;
use Gally\User\Constant\Role;

#[
    ApiResource(
        itemOperations: [
            'menu' => [
                'security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
                'method' => 'GET',
                'path' => 'menu',
                'read' => false,
                'deserialize' => false,
                'controller' => MenuController::class,
            ],
        ],
        collectionOperations: [],
        paginationEnabled: false,
        graphql: [
            'get' => [
                'security' => "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
                'item_query' => MenuResolver::class,
                'read' => false,
                'deserialize' => false,
                'args' => [],
            ],
        ],
    )
]
class Menu
{
    #[ApiProperty(identifier: true)]
    private string $code = 'menu';

    private MenuItem $root;

    public function __construct(MenuItem $root)
    {
        $this->root = $root;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getHierarchy(): array
    {
        return $this->root->asArray()['children'] ?? [];
    }
}
