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

namespace Gally\Index\Repository\Document;

interface DocumentRepositoryInterface
{
    public function index(string $indexName, array $documents, bool $instantRefresh = false): void;

    public function delete(string $indexName, array $documentIds): void;
}
