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

use Gally\Catalog\Entity\LocalizedCatalog;

class IndexTemplate
{
    private ?string $id = null;
    private string $name;
    private ?LocalizedCatalog $localizedCatalog = null;

    private array $indexPatterns;
    private ?int $priority;
    private bool $isDataStreamTemplate;

    /** @var string[] */
    private array $aliases = [];
    private array $mappings = [];
    private array $settings = [];

    /**
     * @param string   $name                 Template name
     * @param array    $indexPatterns        Index patterns to apply template
     * @param int|null $priority             Template priority
     * @param bool     $isDataStreamTemplate Enable data stream for this template
     */
    public function __construct(
        string $name,
        array $indexPatterns,
        ?int $priority = null,
        bool $isDataStreamTemplate = false,
    ) {
        $this->name = $name;
        $this->indexPatterns = $indexPatterns;
        $this->priority = $priority;
        $this->isDataStreamTemplate = $isDataStreamTemplate;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getIndexPatterns(): array
    {
        return $this->indexPatterns;
    }

    public function setIndexPatterns(array $indexPatterns): void
    {
        $this->indexPatterns = $indexPatterns;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function isDataStreamTemplate(): bool
    {
        return $this->isDataStreamTemplate;
    }

    public function setIsDataStreamTemplate(bool $isDataStreamTemplate): void
    {
        $this->isDataStreamTemplate = $isDataStreamTemplate;
    }

    public function getLocalizedCatalog(): ?LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(?LocalizedCatalog $localizedCatalog): void
    {
        $this->localizedCatalog = $localizedCatalog;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string[] $aliases index aliases
     */
    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }

    public function getMappings(): array
    {
        return $this->mappings;
    }

    public function setMappings(array $mappings): void
    {
        $this->mappings = $mappings;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }
}
