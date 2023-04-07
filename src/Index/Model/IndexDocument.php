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

namespace Gally\Index\Model;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Gally\Index\Controller\RemoveIndexDocument;
use Gally\User\Constant\Role;

#[
    ApiResource(
        collectionOperations: [
            'post' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        ],
        graphql: [
            'create' => ['security' => "is_granted('" . Role::ROLE_ADMIN . "')"],
        ],
        itemOperations: [
            'get' => [
                'controller' => NotFoundAction::class,
                'read' => false,
                'output' => false,
            ],
            'remove' => [
                'security' => "is_granted('" . Role::ROLE_ADMIN . "')",
                'method' => 'DELETE',
                'controller' => RemoveIndexDocument::class,
                'read' => false,
                'openapi_context' => [
                    'parameters' => [
                        [
                            'name' => 'indexName',
                            'in' => 'path',
                            'type' => 'string',
                            'required' => true,
                        ],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'document_ids' => ['type' => 'array'],
                                    ],
                                ],
                                'example' => [
                                    'document_ids' => ['1', '2', '3'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        paginationEnabled: false,
    ),
]
class IndexDocument
{
    #[ApiProperty(
        identifier: true
    )]
    private string $indexName;

    /**
     * @var string[]
     */
    private array $documents;

    public function __construct(
        string $indexName,
        array $documents
    ) {
        $this->indexName = $indexName;
        $this->documents = $documents;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function setIndexName(string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): void
    {
        $this->documents = $documents;
    }
}
