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

class IndexStateManagement
{
    private ?string $id = null;
    private string $name;
    private string $indexPattern;
    private ?int $priority;
    private string $description;
    private ?int $deleteAfter;
    private ?int $seqNo = null;
    private ?int $primaryTerm = null;

    /**
     * @param string   $name         Policy name
     * @param string   $indexPattern Index pattern to apply policy
     * @param int|null $priority     Priority of the policy
     * @param string   $description  Policy description
     * @param int|null $deleteAfter  Delete after X days
     */
    public function __construct(
        string $name,
        string $indexPattern,
        ?int $priority = null,
        string $description = '',
        ?int $deleteAfter = null,
    ) {
        $this->name = $name;
        $this->indexPattern = $indexPattern;
        $this->priority = $priority;
        $this->description = $description;
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

    public function getIndexPattern(): string
    {
        return $this->indexPattern;
    }

    public function setIndexPattern(string $indexPattern): self
    {
        $this->indexPattern = $indexPattern;

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
