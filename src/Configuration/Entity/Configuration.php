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
use Gally\Doctrine\Filter\SearchFilter;
use Gally\Doctrine\Filter\VirtualSearchFilter;
use Gally\Metadata\Operation\Bulk;
use Gally\User\Constant\Role;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

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
                                    'path' => 'gally.base_url.media',
                                    'value' => 'https://gally.com/media/',
                                    'scopeType' => 'localized_catalog',
                                ],
                                [
                                    'path' => 'gally.base_url.media',
                                    'value' => 'https://gally.fr/media/',
                                    'scopeType' => 'localized_catalog',
                                    'scopeCode' => 'b2c_fr',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'collection_query',
            normalizationContext: ['groups' => ['configuration:graphql']],
            security: "is_granted('" . Role::ROLE_CONTRIBUTOR . "')",
        ),
    ],
    paginationType: 'page',
    provider: ConfigurationProvider::class,
    processor: ConfigurationProcessor::class,
    normalizationContext: ['groups' => ['configuration:read']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['path' => 'exact'])]
#[ApiFilter(
    filterClass: VirtualSearchFilter::class,
    properties: [
        'language' => ['type' => 'string', 'strategy' => 'ipartial'],
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
    public const SCOPE_LANGUAGE = 'language';
    public const SCOPE_GENERAL = 'general';

    #[ApiProperty(identifier: true)]
    #[Groups(['configuration:read', 'configuration:graphql'])]
    private int $id;

    #[Groups(['configuration:read', 'configuration:graphql'])]
    private string $path;

    private mixed $value;

    #[Groups(['configuration:read', 'configuration:graphql'])]
    private string $scopeType;

    #[Groups(['configuration:read', 'configuration:graphql'])]
    private ?string $scopeCode;

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

    #[SerializedName('value')]
    #[Groups(['configuration:graphql'])]
    public function getValue(): string
    {
        return $this->value;
    }

    #[SerializedName('value')]
    #[Groups(['configuration:read'])]
    public function getDecodedValue(): mixed
    {
        return json_decode($this->value, true);
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
        $this->value = $this->getDecodedValue();

        return $this;
    }
}
