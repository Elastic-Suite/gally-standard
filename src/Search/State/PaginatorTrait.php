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

namespace Gally\Search\State;

use ApiPlatform\Elasticsearch\Serializer\ItemNormalizer;
use Gally\Search\Elasticsearch\DocumentInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

trait PaginatorTrait
{
    public function getPaginatorIterator(iterable $documents): \Traversable
    {
        $denormalizationContext = array_merge([AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true], $this->denormalizationContext);

        /** @var DocumentInterface $document */
        foreach ($documents as $document) {
            $cacheKey = null;
            if (!empty($document->getIndex()) && !empty($document->getInternalId())) {
                $cacheKey = md5(\sprintf('%s_%s', $document->getIndex(), $document->getInternalId()));
            }

            if ($cacheKey && \array_key_exists($cacheKey, $this->cachedDenormalizedDocuments)) {
                $object = $this->cachedDenormalizedDocuments[$cacheKey];
            } else {
                $object = $this->denormalizer->denormalize(
                    $document,
                    $this->resourceClass,
                    ItemNormalizer::FORMAT,
                    $denormalizationContext
                );

                if ($cacheKey) {
                    $this->cachedDenormalizedDocuments[$cacheKey] = $object;
                }
            }

            yield $object;
        }
    }
}
