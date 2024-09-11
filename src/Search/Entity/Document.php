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

namespace Gally\Search\Entity;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Gally\Search\Elasticsearch\DocumentInterface;
use Gally\Search\Resolver\DummyResolver;
use Gally\Search\State\DocumentProvider;

#[ApiResource(
    operations: [
        new Get(controller: NotFoundAction::class, read: false, output: false),
    ],
    graphQlOperations: [
        new QueryCollection(
            name: 'search',
            resolver: DummyResolver::class,
            paginationType: 'page',
            args: [
                'entityType' => [
                    'type' => 'String!',
                    'description' => 'Entity Type',
                ],
                'requestType' => [
                    'type' => 'RequestTypeEnum',
                    'description' => 'Request Type',
                ],
                'localizedCatalog' => [
                    'type' => 'String!',
                    'description' => 'Localized Catalog',
                ],
                'search' => [
                    'type' => 'String',
                    'description' => 'Query Text',
                ],
                'currentPage' => ['type' => 'Int'],
                'pageSize' => ['type' => 'Int'],
                'sort' => ['type' => 'SortInput'],
                'filter' => ['type' => '[FieldFilterInput]', 'is_gally_arg' => true],
            ],
            read: true,
            deserialize: true,
            write: false,
            serialize: true
        ),
    ],
    provider: DocumentProvider::class,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationClientPartial: false,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
class Document implements DocumentInterface
{
    /**
     * @var string
     */
    protected const ID = 'id';

    /**
     * @var string
     */
    protected const INTERNAL_ID = '_id';

    /**
     * @var string
     */
    protected const INDEX = '_index';

    /**
     * @var string
     */
    protected const SCORE_DOC_FIELD_NAME = '_score';

    /**
     * @var string
     */
    protected const SOURCE_DOC_FIELD_NAME = '_source';

    public function __construct(protected array $data = [])
    {
    }

    /**
     * Document ID.
     */
    public function getId(): string
    {
        $source = $this->getSource();

        $id = $source[self::ID] ?? $this->getInternalId();

        return (string) $id;
    }

    /**
     * Document internal ID.
     */
    public function getInternalId(): string
    {
        return (string) $this->data[self::INTERNAL_ID];
    }

    /**
     * Document index.
     */
    public function getIndex(): string
    {
        return $this->data[self::INDEX];
    }

    /**
     * Document score.
     */
    public function getScore(): float
    {
        return $this->data[self::SCORE_DOC_FIELD_NAME] ?? 0;
    }

    /**
     * Document source data.
     */
    public function getSource(): array
    {
        return $this->data[self::SOURCE_DOC_FIELD_NAME] ?? [];
    }

    /**
     * Document raw data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set document raw data.
     *
     * @param array $data Raw data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
