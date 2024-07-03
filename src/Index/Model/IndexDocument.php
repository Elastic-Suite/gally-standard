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

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Action\NotFoundAction;
use Gally\Index\Controller\RemoveIndexDocument;
use Gally\Index\State\DocumentProcessor;
use Gally\User\Constant\Role;

#[ApiResource(
    operations: [
        new Get(
            controller: NotFoundAction::class,
            read: false,
            output: false,
        ),
        new Delete(
            security: "is_granted('" . Role::ROLE_ADMIN . "')",
            controller: RemoveIndexDocument::class,
            read: false,
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'indexName',
                        'in' => 'path', 'type' => 'string',
                        'required' => true
                    ],
                ],
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'document_ids' => [
                                        'type' => 'array'
                                    ],
                                ],
                            ],
                            'example' => [
                                'document_ids' => ['1', '2', '3'],
                            ],
                        ],
                    ],
                ],
            ],
            name: 'remove'
        ),
        new Post(
            security: "is_granted('" . Role::ROLE_ADMIN . "')"
        ),
    ],
    graphQlOperations: [
        new Mutation(
            name: 'create',
            security: "is_granted('" . Role::ROLE_ADMIN . "')"
        ),
    ],
    processor: DocumentProcessor::class,
    paginationEnabled: false,
)]
class IndexDocument
{
    #[ApiProperty(identifier: true)]
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
