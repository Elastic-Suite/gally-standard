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

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Gally\Configuration\Controller\BulkConfigurations;
use Gally\Configuration\State\ConfigurationProcessor;
use Gally\Configuration\State\ConfigurationProvider;
use Gally\Doctrine\Filter\SearchFilterWithDefault;
use Gally\Doctrine\Filter\VirtualSearchFilter;
use Gally\Metadata\Operation\Bulk;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new GetCollection(security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
        new Post(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Put(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Delete(security: "is_granted('" . Role::ROLE_ADMIN . "')"),
        new Bulk(
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
            controller: BulkConfigurations::class,
            uriTemplate: '/configurations/bulk',
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            serialize: true,
            openapiContext: [
                'summary' => 'Add configurations.',
                'description' => 'Add configurations.',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'example' => [
                                [
                                    'path' => 'gally.config.path',
                                    'value' => 'test',
                                    'scopeType' => 'locale',
                                    'scopeCode' => 'fr_FR',
                                ],
                                [
                                    'path' => 'gally.config.path',
                                    'value' => 'test',
                                    'scopeType' => 'locale',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ),
    ],
    graphQlOperations: [
        new QueryCollection(name: 'collection_query', security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')"),
    ],
    paginationType: 'page',
    provider: ConfigurationProvider::class,
    processor: ConfigurationProcessor::class,
)]
#[ApiFilter(
    filterClass: SearchFilterWithDefault::class,
    properties: ['path' => 'ipartial'],
    arguments: ['defaultValues' => ['path' => 'gally']])
]
#[ApiFilter(
    filterClass: VirtualSearchFilter::class,
    properties: [
        'localeCode' => ['type' => 'string', 'strategy' => 'ipartial'],
        'requestType' => ['type' => 'string', 'strategy' => 'ipartial'],
        'localizedCatalogCode' => ['type' => 'string', 'strategy' => 'ipartial'],
    ]
)]
class Configuration
{
    public const SCOPE_LOCALIZED_CATALOG = 'localized_catalog';
    public const SCOPE_REQUEST_TYPE = 'request_type';
    public const SCOPE_LOCALE = 'locale';
    public const SCOPE_GENERAL = 'general';

    #[ApiProperty(identifier: true)]
    private int $id;
    private string $path;
    public mixed $value;
    public string $scopeType;
    public ?string $scopeCode;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getPath(): ?string
    {
        return $this->path ?? null;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = json_encode($value);
    }

    public function getScopeType(): ?string
    {
        return $this->scopeType ?? null;
    }

    public function setScopeType(string $scopeType): void
    {
        $this->scopeType = $scopeType;
    }

    public function getScopeCode(): ?string
    {
        return $this->scopeCode;
    }

    public function setScopeCode(?string $scopeCode): void
    {
        $this->scopeCode = $scopeCode;
    }

    public function decode(): self
    {
        $this->value = json_decode($this->value, true);

        return $this;
    }

    public static function getAvailableScopeTypes(): array
    {
        return [
            self::SCOPE_GENERAL,
            self::SCOPE_LOCALE,
            self::SCOPE_REQUEST_TYPE,
            self::SCOPE_LOCALIZED_CATALOG,
        ];
    }
}
