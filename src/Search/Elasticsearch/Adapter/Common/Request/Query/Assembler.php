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

namespace Gally\Search\Elasticsearch\Adapter\Common\Request\Query;

use Gally\Search\Elasticsearch\Request\QueryInterface;

/**
 * Assemble Elasticsearch queries from search request QueryInterface queries.
 */
class Assembler implements AssemblerInterface
{
    /**
     * @var AssemblerInterface[]
     */
    private array $assemblers;

    /**
     * Constructor.
     *
     * @param AssemblerInterface[] $assemblers Assemblers implementations
     */
    public function __construct(iterable $assemblers = [])
    {
        $assemblers = ($assemblers instanceof \Traversable) ? iterator_to_array($assemblers) : $assemblers;

        $this->assemblers = $assemblers;
    }

    /**
     * Assemble the ES query from a Query.
     *
     * @param QueryInterface $query Query to be assembled
     */
    public function assembleQuery(QueryInterface $query): array
    {
        $assembler = $this->getAssembler($query);

        return $assembler->assembleQuery($query);
    }

    /**
     * Retrieve the specific assembler used to assemble a query.
     *
     * @param QueryInterface $query Query to be assembled
     */
    private function getAssembler(QueryInterface $query): AssemblerInterface
    {
        $queryType = $query->getType();

        if (!isset($this->assemblers[$queryType])) {
            throw new \InvalidArgumentException("Unknown query assembler for {$queryType}.");
        }

        return $this->assemblers[$queryType];
    }
}
