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

namespace Gally\Search\GraphQl\Type\Definition;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use GraphQL\Type\Definition\EnumType;

class SortEnumType extends EnumType implements TypeInterface
{
    public const NAME = 'SortEnum';

    public function __construct()
    {
        $this->name = self::NAME;

        parent::__construct($this->getConfig());
    }

    public function getConfig(): array
    {
        return [
            'values' => [SortOrderInterface::SORT_ASC, SortOrderInterface::SORT_DESC],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
