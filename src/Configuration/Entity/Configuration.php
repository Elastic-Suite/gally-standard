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

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Link;
use Gally\Category\Entity\Category;
use Gally\Configuration\Controller\ConfigurationGet;
use Gally\Configuration\State\ConfigurationProvider;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    operations: [
        new Get(
            controller: NotFoundAction::class,
            read: false,
            output: false
        ),
        new Get(
            uriTemplate: '/configurations/byPath/{path}',
            uriVariables: [
                'path' => new Link(fromClass: Configuration::class, fromProperty: 'path'),
            ],
            controller: ConfigurationGet::class,
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            serialize: true,
            openapiContext: [
                'parameters' => [
                    ['name' => 'path', 'in' => 'path', 'type' => 'string', 'required' => true],
                    ['name' => 'localizedCatalogId', 'in' => 'query', 'type' => 'int'],
                ],
            ],
        ),
        new GetCollection(),
    ],
    graphQlOperations: [new QueryCollection(name: 'collection_query', paginationEnabled: false)],
    provider: ConfigurationProvider::class,
)]
class Configuration
{
    #[ApiProperty(identifier: true)]
    private string $id;
    private string $path;
    public mixed $value;
    public string $scopeType;
    public string $scopeCode;

    public function __construct(string $id, string $path, mixed $value)
    {
        $this->id = $id;
        $this->path = $path;
        $this->value = $value;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
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
        $this->value = $value;
    }

    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    public function setScopeType(string $scopeType): void
    {
        $this->scopeType = $scopeType;
    }

    public function getScopeCode(): string
    {
        return $this->scopeCode;
    }

    public function setScopeCode(string $scopeCode): void
    {
        $this->scopeCode = $scopeCode;
    }
}
