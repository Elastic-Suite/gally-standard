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

namespace Gally\Menu\Entity;

class MenuItem
{
    public function __construct(
        private string $code,
        private ?string $label = null,
        private ?int $order = null,
        private ?string $cssClass = null,
        private ?string $path = null,
        private array $children = []
    ) {
        ksort($this->children);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label ?: $this->code;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(self $child): void
    {
        $this->children[] = $child;

        usort(
            $this->children,
            /**
             * @param self $childA
             * @param self $childB
             */
            function ($childA, $childB) {
                return $childA->getOrder() > $childB->getOrder() ? 1 : -1;
            });
    }

    public function asArray(): array
    {
        $children = [];
        foreach ($this->getChildren() as $child) {
            $children[] = $child->asArray();
        }

        $data = [
            'code' => $this->getCode(),
            'label' => $this->getLabel(),
        ];

        if ($this->getOrder()) {
            $data['order'] = $this->getOrder();
        }
        if ($this->getCssClass()) {
            $data['css_class'] = $this->getCssClass();
        }
        if ($this->getPath()) {
            $data['path'] = $this->getPath();
        }
        if (!empty($children)) {
            $data['children'] = $children;
        }

        return $data;
    }
}
