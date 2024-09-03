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

namespace Gally\Search\Model\Facet;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Search\Resolver\DummyResolver;
use Gally\Search\State\Facet\OptionProvider;

#[ApiResource(
    operations: [
        new Get(controller: NotFoundAction::class, read: false, output: false),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'viewMore',
            resolver: DummyResolver::class,
            read: true,
            deserialize: false,
            args: [
                'entityType' => [
                    'type' => 'String!',
                    'description' => 'Entity Type',
                ],
                'localizedCatalog' => [
                    'type' => 'String!', 'description' => 'Localized Catalog',
                ],
                'aggregation' => [
                    'type' => 'String!', 'description' => 'Source field to get complete aggregation',
                ],
                'search' => [
                    'type' => 'String', 'description' => 'Query Text',
                ],
                'filter' => [
                    'type' => '[FieldFilterInput]', 'is_gally_arg' => true,
                ],
            ]
        ),
    ],
    provider: OptionProvider::class,
    shortName: 'FacetOption',
    paginationEnabled: false
)]
class Option
{
    private string $value;
    private string $label;
    private int $count;

    public function __construct(string $value, string $label, int $count)
    {
        $this->value = $value;
        $this->label = $label;
        $this->count = $count;
    }

    #[ApiProperty(identifier: true)]
    public function getId(): string
    {
        // We need and id field different that the value field because authorized characters in the id field are limited
        // Api platform use this field to build entity URI.
        return str_replace('.', ' ', urlencode($this->value));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
