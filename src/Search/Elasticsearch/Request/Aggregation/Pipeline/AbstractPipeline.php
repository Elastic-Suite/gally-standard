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

namespace Gally\Search\Elasticsearch\Request\Aggregation\Pipeline;

use Gally\Search\Elasticsearch\Request\PipelineInterface;

/**
 * Abstract pipeline implementation.
 */
abstract class AbstractPipeline implements PipelineInterface
{
    /**
     * Pipeline constructor.
     *
     * @param string            $name        Pipeline name
     * @param array|string|null $bucketsPath Pipeline buckets path
     */
    public function __construct(private string $name, private array|string|null $bucketsPath = null)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBucketsPath(): array|string|null
    {
        return $this->bucketsPath;
    }

    public function hasBucketsPath(): bool
    {
        return null !== $this->bucketsPath;
    }
}
