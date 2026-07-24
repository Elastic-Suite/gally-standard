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

namespace Gally\Index\Entity;

/**
 * OpenSearch Transform definition (index-management plugin). Not an ApiResource: this is an
 * internal read model wrapping live OpenSearch state, like IndexTemplate, not something exposed
 * to the Gally admin API.
 */
class Transform
{
    /**
     * @param string      $id           Transform id
     * @param string      $sourceIndex  Source index (or alias/data stream) to aggregate from
     * @param string      $targetIndex  Destination index -- must be a concrete index, not an alias
     * @param array       $groups       Pivot group_by definition
     * @param array       $aggregations Pivot aggregations definition
     * @param bool        $continuous   Whether the transform keeps running incrementally
     * @param bool        $enabled      Whether the transform is enabled
     * @param string|null $description  Human-readable description
     * @param array       $schedule     Execution schedule (interval)
     * @param int         $pageSize     Composite aggregation page size
     */
    public function __construct(
        private string $id,
        private string $sourceIndex,
        private string $targetIndex,
        private array $groups,
        private array $aggregations,
        private bool $continuous = false,
        private bool $enabled = true,
        private ?string $description = null,
        private array $schedule = [],
        private int $pageSize = 1000,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceIndex(): string
    {
        return $this->sourceIndex;
    }

    public function getTargetIndex(): string
    {
        return $this->targetIndex;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function isContinuous(): bool
    {
        return $this->continuous;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSchedule(): array
    {
        return $this->schedule;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Build the "transform" body expected by the OpenSearch Transform API.
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'continuous' => $this->continuous,
            'schedule' => $this->schedule,
            'description' => $this->description,
            'source_index' => $this->sourceIndex,
            'target_index' => $this->targetIndex,
            'page_size' => $this->pageSize,
            'groups' => $this->groups,
            'aggregations' => $this->aggregations,
        ];
    }

    public static function fromResponse(string $id, array $data): self
    {
        return new self(
            $id,
            $data['source_index'],
            $data['target_index'],
            $data['groups'] ?? [],
            $data['aggregations'] ?? [],
            $data['continuous'] ?? false,
            $data['enabled'] ?? true,
            $data['description'] ?? null,
            $data['schedule'] ?? [],
            $data['page_size'] ?? 1000,
        );
    }
}
