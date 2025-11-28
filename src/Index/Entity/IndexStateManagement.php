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

class IndexStateManagement
{
    private ?string $id = null;
    private string $name;
    private ?LocalizedCatalog $localizedCatalog = null;
    private array $indexPatterns;
    private ?int $priority;
    private string $description;
    private ?int $rolloverAfter = null;
    private ?int $deleteAfter;
    private ?int $seqNo = null;
    private ?int $primaryTerm = null;

    /**
     * @param string      $name          Policy name
     * @param array       $indexPatterns Index patterns to apply policy
     * @param int|null    $priority      Priority of the policy
     * @param string|null $description   Policy description
     * @param int|null    $rolloverAfter Rollover after X days
     * @param int|null    $deleteAfter   Delete after X days
     */
    public function __construct(
        string $name,
        array $indexPatterns,
        ?int $priority = null,
        ?string $description = null,
        ?int $rolloverAfter = null,
        ?int $deleteAfter = null,
    ) {
        $this->name = $name;
        $this->indexPatterns = $indexPatterns;
        $this->priority = $priority;
        $this->description = $description ?? '';
        $this->rolloverAfter = $rolloverAfter;
        $this->deleteAfter = $deleteAfter;
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

    public function getLocalizedCatalog(): ?LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(LocalizedCatalog $localizedCatalog): self
    {
        $this->localizedCatalog = $localizedCatalog;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIndexPatterns(): array
    {
        return $this->indexPatterns;
    }

    public function setIndexPatterns(array $indexPatterns): self
    {
        $this->indexPatterns = $indexPatterns;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getDeleteAfter(): ?int
    {
        return $this->deleteAfter;
    }

    public function setDeleteAfter(?int $deleteAfter): self
    {
        $this->deleteAfter = $deleteAfter;

        return $this;
    }

    public function getRolloverAfter(): ?int
    {
        return $this->rolloverAfter;
    }

    public function setRolloverAfter(?int $rolloverAfter): self
    {
        $this->rolloverAfter = $rolloverAfter;

        return $this;
    }

    public function getSeqNo(): ?int
    {
        return $this->seqNo;
    }

    public function setSeqNo(?int $seqNo): self
    {
        $this->seqNo = $seqNo;

        return $this;
    }

    public function getPrimaryTerm(): ?int
    {
        return $this->primaryTerm;
    }

    public function setPrimaryTerm(?int $primaryTerm): self
    {
        $this->primaryTerm = $primaryTerm;

        return $this;
    }
}
